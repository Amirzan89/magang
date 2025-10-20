<?php
namespace App\Http\Middleware;
use App\Http\Controllers\Security\AESController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use Closure;
class DecryptResponse
{
    // private static $testURL = [];
    private static $testURL = ['/verify/create/email', '/verify/create/password'];
    public function handle(Request $request, Closure $next){
        if($request->isMethod('GET') || in_array($request->getPathInfo(), ['/api/handshake']) || in_array($request->getPathInfo(), self::$testURL)){
            return $next($request);
        }
        $resMerseal = app()->make(AESController::class)->mersealToken($request);
        if($resMerseal['status'] == 'error'){
            $codeRes = $resMerseal['statusCode'];
            unset($resMerseal['statusCode']);
            return response()->json($resMerseal, $codeRes);
        }
        $resMerseal = $resMerseal['data'];
        if(!$request->isMethod('GET')){
            $resultData = app()->make(AESController::class)->decryptRequest($request->input('cipher'), $resMerseal['key'], $resMerseal['iv']);
            if($resultData['status'] == 'error'){
                $codeRes = $resultData['statusCode'];
                unset($resultData['statusCode']);
                return response()->json($resultData, $codeRes);
            }
            $request->merge($resultData['data']);
            $request->merge($resMerseal);
            $request->request->remove('cipher');
            $request->request->remove('uniqueid');
            $request->request->remove('mac');
        }
        $request->request->remove('merseal');
        return $next($request);
    }
}