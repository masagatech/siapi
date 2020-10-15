<?php

namespace App\Http\Controllers;

use App\Http\Common\Util;
use Illuminate\Http\Request;
use \Illuminate\Database\QueryException;

class LanguagesettingController extends Controller
{
    public function __construct()
    {
        //
    }
    public function show(Request $request)
    {
        try {
            $type = $request->type;
            $error = false;
            if ($type == 'getEn') {
                $fileData = @$this->readFile('en.json');
                if ($fileData !== false) {
                    $data = json_decode($fileData);
                } else {
                    $error = true;
                    $data = 'File not exist';
                }
            } else if ($type == 'getOtherLang') {
                $value = $request->data;
                $fileData = @$this->readFile(strtolower($value) . '.json');
                if ($fileData !== false) {
                    $data = json_decode($fileData);
                } else {
                    $error = true;
                    $data = 'File not exist';
                }
            } else if ($type == 'get') {
                $data = file_get_contents('./lang/' . $request->lang . '.json');
                return response($data, 200)->header('Content-Type', 'text/plain');
            }
            if ($error) {
                return Util::responseop(response(), 0, null, 'err6', $data);
            } else {
                return Util::responseop(response(), 1, $data, null, null);
            }
        } catch (Exception $ex) {
            return Util::responseop(response(), 0, null, 'err6', $ex->getMessage());
        } catch (QueryException $e) {
            $errorMessage = $e->errorInfo[2];
            return Util::responseop(response(), 0, null, 'err6', $errorMessage);
        }
    }
}
