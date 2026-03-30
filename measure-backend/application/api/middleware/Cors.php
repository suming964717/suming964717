<?php
namespace app\api\middleware;

class Cors
{
    public function handle($request, \Closure $next)
    {
        $origin = $request->header('origin', '*');

        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        if ($request->method() === 'OPTIONS') {
            header('HTTP/1.1 204 No Content');
            exit;
        }

        return $next($request);
    }
}
