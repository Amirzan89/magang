<?php
namespace App\Http\Middleware;
use App\Http\Controllers\UtilityController;
use Illuminate\Http\Request;
use Closure;
class DefaultCSR
{
    private static $exceptPage = ['/sanctum/csrf-cookie',];
    public function handle(Request $request, Closure $next){
        $path = '/' . ltrim($request->path(), '/');
        foreach (self::$exceptPage as $exceptPath) {
            if (strpos($path, $exceptPath) === 0) {
                return $next($request);
            }
        }
        if ($request->header('Accept') !== 'application/json' && !in_array($path, self::$exceptPage)) {
            return $next($request)->header('Cache-Control', 'no-cache, no-store, must-revalidate')->header('Pragma', 'no-cache')->header('Expires', '0');
        }
        return $next($request);
        // if ($request->header('Accept') !== 'application/json' && !in_array($path, self::$exceptPage)) {
        //     return $next($request)->header('Cache-Control', 'no-cache, no-store, must-revalidate')->header('Pragma', 'no-cache')->header('Expires', '0');
        // }else if(!$request->isMethod('get')){
        //     return UtilityController::getView('notfound', ['status'=>'error', 'message'=>'Not Found', 'code' => 404], ['cond' => ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
        // }
        // return $next($request);
    }
}