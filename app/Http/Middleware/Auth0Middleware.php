<?php

namespace App\Http\Middleware;

use App\Http\Common\Util;
use Closure;

class Auth0Middleware
{
    public function handle($request, Closure $next)
    {

        if ($this->isPreflightRequest($request)) {
            return response()->json('option request', 200);
        }
        if (!$request->hasHeader('Authorization')) {
            return response()->json('Authorization Header not found', 401);
        }

        if ($request->header('Authorization') == null) {
            return response()->json('No token provided', 401);
        } else {
            $check_bearer = strpos($request->header('Authorization'), 'Bearer ');
            if ($check_bearer !== false) {
                $token = explode(" ", $request->header('Authorization'));
                $decodedToken = Util::extractToken($token[1]);
                $explodeString = explode("@", $decodedToken);
                $t_id = $explodeString[0];
                $t_date = $explodeString[1];
                if($request->custid==$t_id && strtotime("now")<$t_date)
                {
                    return $next($request);
                } else {
                    return response()->json(['status' => 0, 'result' => null, 'errorcode' => 401, 'msg' => "Wrong token provided"], 200);
                }
            } else {
                return response()->json(['status' => 0, 'result' => null, 'errorcode' => 401, 'msg' => "Wrong token provided"], 200);
            }
        }

    }

    protected function isPreflightRequest($request)
    {
        return $request->isMethod('OPTIONS');
    }

}
