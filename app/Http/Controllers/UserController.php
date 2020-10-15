<?php

namespace App\Http\Controllers;

use App\Http\Common\DBUtil;
use App\Http\Common\Util;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class UserController extends Controller
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
	
	protected function jwt($id) {
        $payload = [
            'iss' => "lumen-jwt", // Issuer of the token
            'sub' => $id, // Subject of the token
            'iat' => time(), // Time when JWT was issued. 
            'exp' => time() + 60*60*48 // Expiration time
        ];
        
        // As you can see we are passing `JWT_SECRET` as the second parameter that will 
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }

    public function login(Request $request)
    {
        $request = json_encode($request->all());
        $sysparam = '{"schema":"sys"}';
        $sql = DBUtil::callProcedure('fn_login', Util::sysParams(''), $request);
        $data=json_decode($sql['res'],true);
        if($data['status']==true){
            $token=$this->jwt($data['result']['userid']);
            $data['result']['token']=$token;
        }
        $response['res'] = json_encode($data);
        return Util::response_sp(response(), $response);
    }

    public function logout(Request $request)
    {
        $request = json_encode($request->all());
        $sysparam = '{"schema":"sys"}';
        $sql = DBUtil::callProcedure('sys.funlogout', $sysparam, $request);
        return $sql;
    }
}
