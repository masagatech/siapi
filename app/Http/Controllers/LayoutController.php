<?php

namespace App\Http\Controllers;

use App\Http\Common\DBUtil;
use App\Http\Common\Util;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;

class LayoutController extends Controller
{
    public function get(Request $request){
        $sql = DBUtil::callFunction('fn_layout', Util::sysParams('sys', $request->operate), json_encode($request->all()));
        $result=Util::makeArray($sql[0],["segmentdata"]);
        $result = ['status' => 1, 'result' => $result, 'errorcode' => null, 'msg' => null];
        //print_r($result);
        return Util::responseop(response(), $result);
    }
    
    public function post(Request $request)
    {
        $params = Util::FilterRequest($request->all());
        $sql = DBUtil::callProcedure('sp_layout', Util::sysParams('sys', $request->operate), $params);
        return Util::response_sp(response(), $sql);
    }
}
