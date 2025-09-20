<?php
namespace App\Http\Middleware;
use App\Http\Controllers\Security\JWTController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use Closure;
class BypassCors
{
    public function handle($request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = [
            'http://localhost:3000',
        ];
        if($request->getMethod() === "OPTIONS"){
            return response('', 200)
            ->header('Access-Control-Allow-Origin', in_array($origin, $allowedOrigins) ? $origin : '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, X-XSRF-TOKEN, x-merseal');
        }
        return $next($request)
            ->header('Access-Control-Allow-Origin', in_array($origin, $allowedOrigins) ? $origin : '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, X-XSRF-TOKEN, x-merseal');
    }
}