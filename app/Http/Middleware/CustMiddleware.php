<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class CustMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        //$token = $request->get('token');
        $rawtoken = explode(" ", $request->header('Authorization'));
        $token = $rawtoken[1];
        if(empty($token) || $token==null) {
            // Unauthorized response if token not there
            return response()->json(['resultKey' =>0, 'resultValue' => null, 'errorCode' => 401, 'defaultError' => 'TOKEN_NOT_PROVIDED'], 200);
        }

        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json(['resultKey' =>0, 'resultValue' => null, 'errorCode' => 400, 'defaultError' => 'TOKEN_EXPIRED'], 200);
        } catch(Exception $e) {
            return response()->json(['resultKey' =>0, 'resultValue' => null, 'errorCode' => 400, 'defaultError' => 'TOKEN_DECODING_ERROR'], 200);
        }

        //$user = User::find($credentials->sub);

        // Now let's put the user in the request class so that you can grab it from there
        // $request->auth = $user;

        return $next($request);
    }
}