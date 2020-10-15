<?php

namespace App\Http\Controllers;

use App\Http\Common\DBUtil;
use App\Http\Common\Util;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;

class AssetController extends Controller
{
    public function get(Request $request){
        if($request->operate=='grid'){
            $record=$this->grid($request);
            return response()->json(['resultKey' => 1, 'resultValue' => $record, 'errorCode' => null], 200);
        }
        else
        {
            $sql = DBUtil::callFunction('fn_asset', Util::sysParams('sys', $request->operate), json_encode($request->all()));
            $result=Util::makeArray($sql[0],["tags"]);
            $result = ['status' => 1, 'result' => $result, 'errorcode' => null, 'msg' => null];
            //print_r($result);
            return Util::responseop(response(), $result);
        }  
    }
    
    public function post(Request $request)
    {
        $params = $request->all();
       
         /**
         * Upload asset file
         */
        if($request->type==4 || $request->type==3 || $request->type==2 || $request->type==1){
            if(!empty($request->file('file'))){
               $fileData=Util::uploader($request->file('file'),false);
                $params['filename']=$fileData['filename'];
                $params['filepath']=$fileData['filepath'];
                $params['filesize']=$fileData['filesize'];
                unset($params['file']); 
            }
            elseif(isset($request['fileData'])){
                $fileData=json_decode($request['fileData'][0],true);
                $params['filename']=$fileData['name'];
                $params['filepath']=$fileData['path'];
                $params['filesize']=$fileData['size'];
            }
            else{
                $params['filename']='';
                $params['filepath']='';
                $params['filesize']=0;
            }
        }
        elseif($request->type==5)
        {
            $fileData=Util::webscreenshot($request->url,false);

        }

        if($request->subtype==2 && $request->type==2){
            $fileData=Util::videothumbnail($request->url,false);
            $params['filename']=$fileData['filename'];
            $params['filepath']=$fileData['filepath'];
            $params['filesize']=$fileData['filesize'];
        }

        $params = Util::FilterRequest($params);
        $sql = DBUtil::callProcedure('sp_asset', Util::sysParams('sys', $request->operate), $params);
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
        $sql = DB::select("select $cols from sys.asset a where true $where order by a.name desc $filterData[3]");
        $totalRows = 0;
        if ($request->getTotalRecordsFlg == 1) {
            $totalRecords = DB::select("select count(1) as total from sys.asset a where true $where OFFSET 0 LIMIT 1")[0];
            $totalRows = $totalRecords->total;
        }
        $query = [$sql, $totalRows];
        return $query;
    }
}
