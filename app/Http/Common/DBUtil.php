<?php

namespace App\Http\Common;

use Illuminate\support\Facades\DB;
use Predis;

class DBUtil
{
    public function __construct()
    {
        //
    }

    public static function callFunction($funcName, $sysparams, $params, $resultcount = 1)
    {
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $q = "select func.$funcName('$sysparams','$params','result');fetch all IN result;";


        $stmt = $pdo->prepare($q);
        $exec = $stmt->execute();
        $results = [];
        if ($exec) {
            $results[] = $stmt->fetchAll(\PDO::FETCH_OBJ);

            //$results[] = $stmt->fetchAll(\PDO::FETCH_OBJ);
            return $results;
        }

        return false;
        # code...
    }

    public static function callView($statement, $sysparams)
    {
        $schema = $sysparams['schema'];
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $q = "SET search_path TO $schema;" . $statement;
        $stmt = $pdo->prepare($q);
        $exec = $stmt->execute();
        $results = [];
        if ($exec) {
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            //$results[] = $stmt->fetchAll(\PDO::FETCH_OBJ);
            return $results;
        }

        return $results;
        # code...
    }

    public static function callProcedure($spName, $sysparam, $params)
    {
        $q = "call func.$spName('" . $sysparam . "','" . $params . "')";
        $exec = DB::select($q);
        $result = (array) $exec[0];
        return $result;
    }

    public static function getDB()
    {

        return \DB;
    }



    public static function getRedisData($key, $default='')
    {
        $client = new Predis\Client();
        return $client->get($key);
        # code...
    }

    public static function setRedisData($key, $value)
    {
        $client = new Predis\Client();
        $client->set($key, json_encode($value));
        # code...
    }


    public static function getAuthData($dispid)
    {
        $client = new Predis\Client();
        $cldata = $client->get($dispid);
        return json_decode($client->get($dispid), true);
    }

    public static function setAuthData($dispid, $data)
    {
        $client = new Predis\Client();
        $main = [];
        if (!empty($client->get($dispid))) {
            $main = json_decode($client->get($dispid), true);
        }
        $main['access_token'] = $data['access_token'];
        $main['expires_at'] = $data['expires_at'];
        return $client->set($dispid, json_encode($main));
    }
	public static function delRedisData($key)
    {
        $client = new Predis\Client();
        $client->del($key);
        # code...
    }
}
