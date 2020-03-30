<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Exception;
const MAX_ROW_LEN = 1000;
const FILE_NAME = "maskdata.csv";


/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Mask Task Laravel",
 *      description="A Laravel Version Mask Map",
 *      @OA\Contact(
 *          email="russell.tseng@104.com.tw"
 *      ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 */

/**
 *  @OA\Server(
 *      url="http://localhost",
 *      description="Local Host"
 *  )
 *
 *  @OA\Server(
*      url="https://projects.dev/api/v1",
 *      description="L5 Swagger OpenApi Server"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     type="oauth2",
 *     description="Use a global client_id / client_secret and your username / password combo to obtain a token",
 *     name="Password Based",
 *     in="header",
 *     scheme="https",
 *     securityScheme="Password Based",
 *     @OA\Flow(
 *         flow="password",
 *         authorizationUrl="/oauth/authorize",
 *         tokenUrl="/oauth/token",
 *         refreshUrl="/oauth/token/refresh",
 *         scopes={}
 *     )
 * )
 */

/**
 * @OA\Tag(
 *     name="Mask Task Project",
 *     description="A Laravel Version Mask Map"
 * )
 * 
 * @OA\ExternalDocumentation(
 *     description="To the project->",
 *     url="https://github.com/chumicat/maskTaskLaravel"
 * )
 */

/**
 * @OA\Get(
 *      path="/maskTask",
 *      operationId="getProjectsList",
 *      tags={"Projects"},
 *      summary="Search Mask",
 *      description="Returns corresponded data",
 *      @OA\Parameter(
 *          name="i",
 *          description="institution keyword",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *      @OA\Parameter(
 *          name="a",
 *          description="address keyword",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *      @OA\Response(response=200, description="successful operation"),
 *      @OA\Response(response=400, description="Bad request"),
 *      @OA\Response(response=404, description="Resource Not Found"),
 *      security={
 *           {"api_key_security_example": {}}
 *      }
 * )
 *
 * Returns list of projects
 */


class MaskTaskController extends Controller {
    private $data;
    private $lastUpdateTime;
    private function adjustText($text = null)
    {
        $text = str_replace(["Ｏ", "0", "˙", "．", "，", "-", "－"], ["零", "零", "、", "、
        ", "、", "之", "之"], $text);
        $text = str_replace(["０", "１", "２", "３", "４", "５", "６", "７", "８", "９"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $text);
        $text = str_replace(["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"], ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"], $text);
        $text = str_replace(["台", "F", "f", "Ｆ", "ｆ"], ["臺", "樓", "樓", "樓", "樓"], $text);
        return $text;
    }

    private function adjustdata()
    {
        foreach ($this->data as &$row) {
            $row["機構名稱"] = $this->adjustText($row["機構名稱"]);
            $row["機構地址"] = $this->adjustText($row["機構地址"]);
        }
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
        $this->adjustdata();
        $this->filter($r);
        usort($this->data, function ($a, $b) {
            return $a["成人口罩"] < $b["成人口罩"];
        });
        $this->show($r);
    }
}