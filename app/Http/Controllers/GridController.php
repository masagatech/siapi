<?php

namespace App\Http\Controllers;

use App\Http\Common\DBUtil;
use App\Http\Common\Util;
use App\Savedsearch;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;
use \Illuminate\Database\QueryException;

class GridController extends Controller
{
    public function __construct()
    {
        //
    }
    public function getColumns(Request $request)
    {
        try {
            $module = $request->module;
            $query = DBUtil::callFunction('fn_grid', json_encode(['schema' => 'sys', 'operate' => 'getcolumn']), json_encode($request->all()));
            if (count($query) > 0) {
                $filter = $query[0];
                return response()->json(['resultKey' => 1, 'resultValue' => json_decode($filter[0]->filterlist), 'errorCode' => null], 200);
            } else {
                return Util::responseop(response(), 0, null, "err6", "Error in Procedure");
            }
        } catch (Exception $ex) {
            return Util::responseop(response(), 0, null, "err6", $ex->getMessage());
        } catch (QueryException $e) {
            $errorMessage = $e->errorInfo[2];
            return Util::responseop(response(), 0, null, 'err6', $errorMessage);
        }
    }
    public function show(Request $request)
    {
        DB::statement("SET search_path = sys");
        try {
            $flag = $request->type;
            if ($flag == 'all') {
                $query = DB::table('vw_grid_master')->get();
            } else if ($flag == 'allmodules') {
                $query = DB::table('vw_grid_master')->select('module')->distinct()->orderBy('module', 'Asc')->get();
            } else if ($flag == 'getModuleData') {
                $query = DB::table('vw_grid_master')->where(['module' => $request->name, 'isdelete' => 0])->orderBy('column_order', 'Asc')->get();
            }
            if ($query == true) {
                return response()->json(['resultKey' => 1, 'resultValue' => $query, 'errorCode' => null], 200);
            } else {
                return response()->json(['resultKey' => 0, 'resultValue' => null, 'errorCode' => 'err2', 'defaultError' => 'No records found'], 200);
            }
        } catch (Exception $ex) {
            return response()->json(['resultKey' => 0, 'resultValue' => null, 'errorCode' => 'err6', 'defaultError' => $ex->getMessage()], 200);
        } catch (QueryException $e) {
            $errorMessage = $e->errorInfo[2];
            return response()->json(['resultKey' => 0, 'resultValue' => null, 'errorCode' => 'err6', 'defaultError' => $errorMessage], 200);
        }
    }
    public function addAdvSearchData(Request $request)
    {
        try {
            $errcode = 0;
            $error = null;
            $resultkey = null;
            $resultvalue = null;
            $data = $request->all();
            $query = DBUtil::callProcedure("sp_grid", '{"schema":"sys","operate":"crud"}', json_encode($data));

            if ($query == true) {
                return response()->json(['resultKey' => 1, 'resultValue' => json_decode($query['res']), 'errorCode' => null], 200);
            } else {
                return response()->json(['resultKey' => 0, 'resultValue' => null, 'errorCode' => 'err', 'defaultError' => 'Error in Procedure'], 200);
            }
        } catch (Exception $ex) {
            return response()->json(['resultKey' => 0, 'resultValue' => null, 'errorCode' => 'err', 'defaultError' => $ex->getMessage()], 200);
            //return Util::responseop(response(), 0, null, "err6", $ex->getMessage());
        } catch (QueryException $e) {
            $errorMessage = $e->errorInfo[2];
            return response()->json(['resultKey' => 0, 'resultValue' => null, 'errorCode' => 'err', 'defaultError' => $errorMessage], 200);
        }
    }

    public function removeAdvSearchData(Request $request)
    {
        try {
            $errcode = 0;
            $error = null;
            $resultkey = null;
            $resultvalue = null;
            $query = Savedsearch::where('id', $request->id)->update(['isdelete' => 1]);
            if ($query) {
                return Util::responseop(response(), 1, $query, null, "");
            } else {
                return Util::responseop(response(), 0, null, "err6", "Error");
            }
        } catch (Exception $ex) {
            return Util::responseop(response(), 0, null, "err6", $ex->getMessage());
        } catch (QueryException $e) {
            $errorMessage = $e->errorInfo[2];
            return Util::responseop(response(), 0, null, 'err6', $errorMessage);
        }
    }

    public function getSavedSearch(Request $request)
    {
        try {
            $createdby = $request->userid;
            $module = $request->module;
            $type = $request->type;
            if ($type === 'ddl') {
                $query = Savedsearch::select(['id', DB::raw('filtername as filterName'), DB::raw('filterdata as filterData')])->where(['createdby' => $createdby, 'module' => $module, 'active' => 1, 'isdelete' => 0])->get();
            } else if ($type === 'id') {
                $id = $request->id;
                $query = Savedsearch::where(['id' => $id])->first();
            }

            if ($query) {
                return Util::responseop(response(), 1, $query, null, null);
            } else {
                return Util::responseop(response(), 0, null, null, null);
            }
        } catch (Exception $ex) {
            return Util::responseop(response(), 0, null, "err6", $ex->getMessage());
        } catch (QueryException $e) {
            $errorMessage = $e->errorInfo[2];
            return Util::responseop(response(), 0, null, 'err6', $errorMessage);
        }
    }

    public function getDatasource(Request $request)
    {

        $sql = DBUtil::callFunction('fn_griddatasource', Util::sysParams($request->cmp, $request->operate), json_encode($request->all()));
        $result = ['status' => 1, 'result' => $sql[0], 'errorcode' => null, 'msg' => null];
        return Util::responseop(response(), $result);
    }
}
