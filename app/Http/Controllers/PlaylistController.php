<?php

namespace App\Http\Controllers;

use App\Http\Common\DBUtil;
use App\Http\Common\Util;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;

class PlaylistController extends Controller
{
    public function get(Request $request){
        if($request->operate=='grid'){
            $record=$this->grid($request);
            return response()->json(['resultKey' => 1, 'resultValue' => $record, 'errorCode' => null], 200);
        }
        else
        {
            $sql = DBUtil::callFunction('fn_playlist', Util::sysParams('sys', $request->operate), json_encode($request->all()));
            $result=Util::makeArray($sql[0],["assets"]);
            $result = ['status' => 1, 'result' => $result, 'errorcode' => null, 'msg' => null];
            //print_r($result);
            return Util::responseop(response(), $result);
        }
    }
    
    public function post(Request $request)
    {
        $params = Util::FilterRequest($request->all());
        $sql = DBUtil::callProcedure('sp_playlist', Util::sysParams('sys', $request->operate), $params);
        return Util::response_sp(response(), $sql);
    }

    protected function grid($request){
        $filterData = Util::filter($request->filter);
        $cols = $filterData[0];
        $req = $request->filter;
        $where = "";
        if ($req['inputFilter']['column_name'] == 'name' && $req['inputFilter']['keyword'] != '') {
            $where .= " and a.name LIKE '%" . $req['inputFilter']['keyword'] . "%', ";
        }
        $where = substr($where, 0, -2);
        $sql = DB::select("select $cols from sys.playlist_master a where true $where order by a.name desc $filterData[3]");
        $totalRows = 0;
        if ($request->getTotalRecordsFlg == 1) {
            $totalRecords = DB::select("select count(1) as total from sys.playlist_master a where true $where OFFSET 0 LIMIT 1")[0];
            $totalRows = $totalRecords->total;
        }
        $query = [$sql, $totalRows];
        return $query;
    }
}
