<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Exception;
const MAX_ROW_LEN = 1000;
const FILE_NAME = "maskdata.csv";

class MaskTaskController extends Controller {
    private $data;
    private $lastUpdateTime;
    public function filtering($header, $keyword) {
        $ret = [];
        foreach ($this->data as $row) {
            if (strpos($row[$header], $keyword) !== false)
                $ret[] = $row;
        }
        $this->data = $ret;
    }

    public function filter(Request $r) {
        if ($r->query('i')) {
            $this->filtering("機構名稱", $r->query('i'));
        }
        if ($r->query('d')) {
            $this->filtering("機構地址", $r->query('d'));
        }
    }

    public function show(Request $r) {
        echo "Last up data: $this->lastUpdateTime<br>";
        echo $r->query("a")."<br>";
        echo "<table border=\"1\">";
        echo "<tr>";
        echo "<td>機構名稱</td><td>機構地址</td><td>機構電話</td><td>成人口罩</td><td>孩童口罩</td>";


        for ($i=0; isset($this->data[$i]); ++$i){
            echo "</tr><tr>";
            echo "<td>".$this->data[$i]["機構名稱"]."</td>";
            echo "<td>".$this->data[$i]["機構地址"]."</td>";
            echo "<td>".$this->data[$i]["機構電話"]."</td>";
            echo "<td>".$this->data[$i]["成人口罩"]."</td>";
            echo "<td>".$this->data[$i]["孩童口罩"]."</td>";
        }
        echo "</tr>";
        echo "</table>";
    }

    public function search(Request $r) {
        system("curl -L --max-time 5 -O \"http://data.nhi.gov.tw/Datasets/Download.ashx?rid=A21030000I-D50001-001&l=https://data.nhi.gov.tw/resource/mask/maskdata.csv\"");
        if (!file_exists(FILE_NAME)) {
            return "Fail to get data";
        }

        $fp = fopen(FILE_NAME, "r");
        $title = fgetcsv($fp, MAX_ROW_LEN); // not used title
        for ($rowid = 0; ($row = fgetcsv($fp, MAX_ROW_LEN)) !== false; ++$rowid) {
            $this->data[$rowid]["機構名稱"] = $row[1];
            $this->data[$rowid]["機構地址"] = $row[2];
            $this->data[$rowid]["機構電話"] = $row[3];
            $this->data[$rowid]["成人口罩"] = (int)$row[4];
            $this->data[$rowid]["孩童口罩"] = (int)$row[5];
            $this->lastUpdateTime = $row[6];
        }
        $this->filter($r);
        $this->show($r);
    }
}
