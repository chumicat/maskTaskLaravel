<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Exception;
const MAX_ROW_LEN = 1000;
const FILE_NAME = "maskdata.csv";

class MaskTaskController extends Controller {
    private $data;
    private $lastUpdateTime;
    private function adjustText($text = null)
    {
        // 我把大部分時間用在共同專案上了
        // 沒時間了，所以不整理
        // 寫測試到快死了～～
        if ($text === null) {
            foreach ($this->data as &$row) {
                $row["機構名稱"] = str_replace(["Ｏ", "0", "˙", "．", "，", "-", "－"], ["零", "零", "、", "、", "、", "之", "之"], $row["機構名稱"]);
                $row["機構名稱"] = str_replace(["０", "１", "２", "３", "４", "５", "６", "７", "８", "９"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $row["機構名稱"]);
                $row["機構名稱"] = str_replace(["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $row["機構名稱"]);
                $row["機構名稱"] = str_replace(["台", "F", "f", "Ｆ", "ｆ"], ["臺", "樓", "樓", "樓", "樓"], $row["機構名稱"]);
                $row["機構地址"] = str_replace(["Ｏ", "0", "˙", "．", "，", "-", "－"], ["零", "零", "、", "、", "、", "之", "之"], $row["機構地址"]);
                $row["機構地址"] = str_replace(["０", "１", "２", "３", "４", "５", "６", "７", "８", "９"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $row["機構地址"]);
                $row["機構地址"] = str_replace(["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $row["機構地址"]);
                $row["機構地址"] = str_replace(["台", "F", "f", "Ｆ", "ｆ"], ["臺", "樓", "樓", "樓", "樓"], $row["機構地址"]);
            }
            return;
        }
        $text = str_replace(["Ｏ", "0", "˙", "．", "，", "-", "－"], ["零", "零", "、", "、
        ", "、", "之", "之"], $text);
        $text = str_replace(["０", "１", "２", "３", "４", "５", "６", "７", "８", "９"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $text);
        $text = str_replace(["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $text);
        $text = str_replace(["台", "F", "f", "Ｆ", "ｆ"], ["臺", "樓", "樓", "樓", "樓"], $text);
        return $text;
    }

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
            $this->filtering("機構名稱", $this->adjustText($r->query('i')));
        }
        if ($r->query('d')) {
            $this->filtering("機構地址", $this->adjustText($r->query('d')));
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
        $this->adjustText();
        $this->filter($r);
        usort($this->data, function ($a, $b) {
            return $a["成人口罩"] < $b["成人口罩"];
        });
        $this->show($r);
    }
}
