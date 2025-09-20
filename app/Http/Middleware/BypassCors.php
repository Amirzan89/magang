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
        \Log::info('Middleware jalan', [$request->method()]);
        $allowedOrigins = [
            'http://localhost:3000',
        ];
        \Log::info('CORS Middleware triggered1', [
            'origin' => $origin,
            'method' => $request->getMethod(),
        ]);
        
        if($request->getMethod() === "OPTIONS"){
            return response('', 200)
            ->header('Access-Control-Allow-Origin', $origin ?? '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, X-XSRF-TOKEN, x-merseal');
        }
        \Log::info('CORS Middleware triggered', ['path' => $request->path()]);
        return $next($request)
            ->header('Access-Control-Allow-Origin', $origin ?? '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, X-XSRF-TOKEN, x-merseal');
    }
}