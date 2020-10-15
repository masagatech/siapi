<?php

namespace App\Http\Controllers;
use App\Menu;

use App\Http\Common\DBUtil;
use Illuminate\support\Facades\DB;
use App\Http\Common\Util;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function get(Request $request)
    {
        $type = $request->type;
        $params = $request->all();
        $response='';
        if ($type == 'usermenus') {
            //echo  json_encode($params);
            $sql = DBUtil::callFunction('fn_menu', Util::sysParams('sys'), json_encode($params));
            $response=$sql[0];
        } elseif ($type == 'admin') {
            DB::statement("SET search_path = sys");
            $sql = Menu::select(['id', 'menu as label', 'route as url', 'isclickable', 'action', 'level', 'parent_id as parentId'])->where('isactive', true)->orderby('level')->get();
            $response=$sql;
        }
        return response()->json(['resultKey' => 1, 'resultValue' => $response, 'errorCode' => null], 200);
    }

}
