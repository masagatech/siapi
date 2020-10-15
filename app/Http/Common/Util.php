<?php

namespace App\Http\Common;

use Illuminate\support\Facades\DB;

class Util
{
    static $hash;
    public function __construct()
    {
        //
    }

    public static function responseop($response, $record)
    {
        $data = $record;
        return $response->json(['resultKey' => $data['status'], 'resultValue' => $data['result'], 'errorCode' => $data['errorcode'], 'defaultError' => $data['msg']], 200);
    }

    public static function response_sp($response, $record)
    {
        $data = json_decode($record['res'], true);
        $status = ($data['status']) ? 1 : 0;
        $errorcode = (isset($data['errorcode'])) ? $data['errorcode'] : null;
        $msg = (isset($data['msg'])) ? $data['msg'] : null;

        if (isset($data['extras'])) {
            unset($data['extras']);
        }
        return $response->json(['resultKey' => $status, 'resultValue' => $data, 'errorCode' => $errorcode, 'defaultError' => $msg], 200);
    }

    public static function respond($response, $record)
    {
        $data = $record;
        return $response->json(['resultKey' => $data['status'], 'resultValue' => $data['result'], 'errorCode' => $data['errorcode'], 'defaultError' => $data['msg']], 200);
    }

    public static function filter($filter)
    {

        $start = $filter['first'];
        $limit = $filter['rows'];
        $cols = json_decode($filter['cols']);
        $case = [];

        if (isset($filter['case'])) {
            $case = $filter['case'];
            foreach ($cols as $key => $value) {
                # code...
                if (isset($case[$value])) {

                    $s = "case when " . $value . " = " . $case[$value];
                    $s = str_replace("?", " then '", $s);
                    $s = str_replace(":", "' else '", $s);
                    $s .= "' end as \"" . $value . '"';
                    $cols[$key] = $s;
                }
            }

        }
        $cols = (count($cols) > 0) ? implode(",", $cols) : '*';
        $sortField = "";
        $sortOrder = "";
        /* if ($filter['sortField'] != null) {
        $sortOrder = ($filter['sortOrder'] == 1) ? 'Asc' : 'Desc';
        $sortField = "order by " . $filter['sortField'] . " $sortOrder";
        } */

        if (isset($filter['multiSortMeta']) and $filter['multiSortMeta'] != '') {
            for ($i = 0; $i < count($filter['multiSortMeta']); $i++) {
                $a = $filter['multiSortMeta'][$i];
                $sortOrder .= $a['field'] . " " . ($a['order'] == -1 ? 'ASC' : 'DESC') . ",";
            }
            $sortField = "order by " . substr($sortOrder, 0, -1);
        }
        $limitField = "";
        $limitField = " OFFSET " . $start . " LIMIT " . $limit;
        $data = (isset($filter['filters']) && $filter['filters'] != '') ? $filter['filters'] : [];
        if (count($data) > 0) {
            $where = "";
            foreach ($data as $a) {
                if ($a['sign'] === 'LIKE') {
                    $where .= " " . $a['column'] . " " . $a['sign'] . " '%" . $a['inputValue'] . "%' " . $a['condition'];
                } else {
                    $where .= " " . $a['column'] . " " . $a['sign'] . " '" . $a['inputValue'] . "' " . $a['condition'];
                }
            }
        } else {
            $where = "true";
        }

         if (isset($filter['inputFilter']) && $filter['inputFilter']['keyword'] != '') {
            if ($filter['inputFilter']['sign'] === 'LIKE') {
                 $where .= " AND lower(" . $filter['inputFilter']['column_name'] . ") LIKE '%" . strtolower($filter['inputFilter']['keyword']) . "%'";
				
				
            } else {
                $where .= " AND " . $filter['inputFilter']['column_name'] . " = '" . $filter['inputFilter']['keyword'] . "'";
            }
        }
        $tempcols = [];
        foreach (explode(",", $cols) as $key => $value) {
            if (strpos($value, 'as') === false) {

                array_push($tempcols, $value . ' as "' . $value . '"');
            } else {
                array_push($tempcols, $value);
            }

            # code...
        }
        $cols = implode(",", $tempcols);

        //str_replace(',',' as ,',strstr($value,'.')));

        return [$cols, $where, $sortField, $limitField];
    }

    public static function getUserBasicDetails($uid)
    {
        $sql = DB::select("call SPGetUserBasicDetails(" . $uid . ")");
        if (!empty($sql)) {
            return $sql[0];
        } else {
            return '';
        }
    }
    public static function getGeneralSettings()
    {
        $sql = DB::table("settings")->select('jsondata')->where(['type' => 'GENERAL'])->first();
        return json_decode($sql->jsondata, true);
    }

    public static function getTypewiseSettings($type)
    {
        $sql = DB::table("settings")->select('jsondata')->where(['type' => $type])->first();
        return json_decode($sql->jsondata, true);
    }

    public static function todecimal($decimal, $value)
    {
        return number_format($value, $decimal, '.', '');
    }

    public static function callRow($procName, $parameters = null, $isExecute = false)
    {
        $syntax = '';
        for ($i = 0; $i < count($parameters); $i++) {
            $syntax .= (!empty($syntax) ? ',' : '') . '?';
        }
        $syntax = 'CALL ' . $procName . '(' . $syntax . ');';

        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $stmt = $pdo->prepare($syntax, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
        for ($i = 0; $i < count($parameters); $i++) {
            $stmt->bindValue((1 + $i), $parameters[$i]);
        }
        $exec = $stmt->execute();
        if (!$exec) {
            return $pdo->errorInfo();
        }

        if ($isExecute) {
            return $exec;
        }

        $results = [];
        do {
            try {
                $results[] = $stmt->fetchAll(\PDO::FETCH_OBJ);
            } catch (\Exception $ex) {}
        } while ($stmt->nextRowset());

        if (1 === count($results)) {
            return $results[0];
        }

        return $results;
    }

    public static function HandleCurl($type,$header,$request, $url = '')
    {
        $ch = curl_init();
        $requestData = $request;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($type=='POST'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public static function wlog($module, $data, $lineno = 0, $append = true)
    {
        // if ($error) {
        //     file_put_contents("maillog.txt", PHP_EOL . date('Y-m-d H:i:s'), FILE_APPEND);
        // }
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        $data = PHP_EOL . date('Y-m-d H:i:s') . " line >> " . $lineno . " >> " . PHP_EOL . $data;

        if ($append) {
            file_put_contents($module . ".txt", $data, FILE_APPEND);
        } else {
            file_put_contents($module . ".txt", $data);
        }
    }
    public static function sysParams($cmp='sys', $operation = '', $flag = '', $payload = '')
    {
        $sysparams = array("schema" => $cmp, "operate" => $operation, "flag" => $flag, "payload" => $payload);
        if (is_numeric($cmp)) {
            $sysparams['schema'] = 'cmp' . $cmp;
            return json_encode($sysparams);
        }

        return json_encode($sysparams);
        # code...
    }
    public static function sysBuildSchema($cmp)
    {
        $sysparams = array("schema" => $cmp);
        if (is_numeric($cmp)) {
            $sysparams['schema'] = 'cmp' . $cmp;
            return $sysparams;
        }

        return $sysparams;
        # code...
    }

    public static function makeSchema($cmp)
    {
        if (is_numeric($cmp)) {
            return 'cmp' . $cmp;
        }

        return $cmp;
        # code...
    }

    public static function FilterRequest($request)
    {
        if (!empty($request)) {
            $convertToJson = json_encode($request);
            $request = str_replace('\"', '\\"', $convertToJson); // Escape Double Quote
            $request = str_replace("'", "\\'", $request); // Escape Single Quote
            $request = str_replace("\n", " ", $request); // Escape new line
        }
        return $request;
    }

    public static function modifyInputFilter($filter, $col)
    {
        if (isset($filter['inputFilter'])) {
            foreach ($filter['inputFilter'] as $a) {
                if ($a['sign'] === 'LIKE') {
                    $where .= " AND " . $a['column_name'] . " LIKE '%" . $a['keyword'] . "%'";
                } else {
                    $where .= " AND " . $a['column_name'] . " = '" . $a['keyword'] . "'";
                }
            }
        }
    }

    public static function makeArray($array,$keys){
        $array=json_decode(json_encode($array),true);
        foreach($array as $key=>&$value){
            foreach($keys as $a){
                if(in_array($a,array_keys($value))){
                    $value[$a]=json_decode($value[$a],true);
                }
            }
        }
        return $array;
    }

    public static function uploader($files,$ismultiple=true){
        $uploadedFile = [];
        foreach ($files as $key => $file) {
            $destinationPath = './documents/';
            $fileTempName = explode(".", $file->getClientOriginalName());
            $fileName = str_replace(' ', '_', $fileTempName[0]) . "_" . time() . "." . $file->getClientOriginalExtension();
            $file->move($destinationPath, $fileName);
            array_push($uploadedFile, [ 'filename' => $fileTempName[0], 'filepath' => $fileName, 'filesize' => 0]);
        }
        if(!$ismultiple) { return $uploadedFile[0]; } else {return $uploadedFile;}
    }

    public static function webscreenshot($url){
        $params = http_build_query(array(
                    "access_key" => "4077463db97540b2945706372e294aca",
                    "url" => $url,
                ));
        $filename='webscreenshoot_'.time().'.jpeg';
        $directory='./documents/';
        //https://img.youtube.com/vi/SSFZHqu5fCo/mqdefault.jpg
        $image_data = file_get_contents("https://api.apiflash.com/v1/urltoimage?" . $params);
        file_put_contents($directory."".$filename, $image_data);
        return [ 'filename' => $url, 'filepath' => $filename, 'filesize' => 0];
    }

    public static function videothumbnail($url){
        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        $videoid=$query['v'];
        $filename=$videoid.'_'.time().'.jpeg';
        $directory='./documents/';
        $videoImgUrl="https://img.youtube.com/vi/".$videoid."/hqdefault.jpg";
        $image_data = file_get_contents($videoImgUrl);
        file_put_contents($directory."".$filename, $image_data);
        return [ 'filename' => $url, 'filepath' => $filename, 'filesize' => 0];
    }
}
