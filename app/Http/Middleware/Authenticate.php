<?php
namespace App\Http\Middleware;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Pages\AdminController as ShowAdminController;
use App\Http\Controllers\Security\JWTController;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\UtilityController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Closure;
class Authenticate
{
    private static $metaDelCookie;
    public function __construct(){
        self::$metaDelCookie = [
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
    }
    private function handleRedirect($request, $utilityController, $aesController, $cond){
        if($cond['isCondition'] == 'error'){
            setcookie('token1', '', ['expires'  => time() - 3600, ...self::$metaDelCookie]);
            setcookie('token2', '', ['expires'  => time() - 3600, ...self::$metaDelCookie]);
            return !$request->isMethod('get') || $request->wantsJson() ? $utilityController->getView($request, $aesController, '', ['message'=>$cond['message']], 'json_encrypt', $cond['statusCode']) : redirect('/login');
        }
        return !$request->isMethod('get') || $request->wantsJson() ? $utilityController->getView($request, $aesController, '', ['message'=>'redirect'], 'json_encrypt', 302) : redirect($cond['link']);
    }
    public function handle(Request $request, Closure $next){
        $jwtController = app()->make(JWTController::class);
        $aesController = app()->make(AESController::class);
        $adminController = app()->make(ShowAdminController::class);
        $utilityController = app()->make(UtilityController::class);
        $currentPath = $request->getPathInfo();
        $previousUrl = url()->previous();
        $path = parse_url($previousUrl, PHP_URL_PATH);
        $isPath = function($path, $inPage, $inPrefAuth){
            return in_array($path, $inPage) || !empty(array_filter($inPrefAuth, fn($prefix) => str_starts_with($path, $prefix)));
        };
        $delCookie = function($inpToken, $name){
            $expiryTime = null;
            $currentTime = time();
            $expiryTime = intval($inpToken['exp']);
            if($expiryTime && $expiryTime < $currentTime){
                setcookie($name, '', ['expires'  => time() - 3600, ...self::$metaDelCookie]);
                return ['status'=>'error'];
            }
            return ['status'=>'success'];
        };
        if(isset($_COOKIE['token2'])){
            $token2 = json_decode($_COOKIE['token2'], true);
            if(!isset($token2['value']) && !isset($token2['exp'])){
                return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'Invalid Token','statusCode'=>400]);
            }
            $delToken2 = $delCookie($token2, 'token2');
            if($delToken2['status'] == 'error'){
                return $this->handleRedirect($request, $utilityController, $aesController, 'error');
            }
            $publicPage = ['/', '/about', '/events', '/login', '/password/reset', '/verify/password', '/verify/email', '/auth/redirect', '/auth/google'];
            $prefPublic = ['/event/'];
            if($isPath($currentPath, $publicPage, $prefPublic) || $isPath($currentPath, array_map(fn($path) => '/api' . $path, $publicPage), array_map(fn($path) => '/api' . $path, $prefPublic))){
                if(in_array(ltrim($path), $publicPage)){
                    $response = $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'success','link'=>'/dashboard']);
                }else{
                    $prefixes = ['/admin/download'];
                    $response = null;
                    foreach($prefixes as $prefix){
                        if($prefix !== '' && strpos($path, $prefix) === 0){
                            $response = $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'success','link'=>'/dashboard']);
                        }
                    }
                    if(is_null($response) && $request->isMethod('get') && !in_array($path, ['/download'])){
                        $response = $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'success','link' => $path ?? '/dashboard']);
                    }
                }
                return $response;
            }
            if(!$jwtController->checkExistRefreshToken($token2['value'])){
                return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'Sesi Habis','statusCode'=>400]);
            }
            $decodedRefresh = $jwtController->decode($request, $utilityController, $token2['value'], 'JWT_SECRET_REFRESH_TOKEN');
            if($decodedRefresh['status'] == 'error'){
                if($decodedRefresh['message'] == 'Expired token'){
                    return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'Expired token','statusCode'=>500]);
                }
                return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>$decodedRefresh['message'],'statusCode'=>500]);
            }
            // //check user is exist in database
            $userDb = User::select('id_user', 'nama_lengkap', 'jenis_kelamin', 'no_telpon', 'email', 'foto','google_id')->where('uuid', $decodedRefresh['data']['user'])->first();
            if(is_null($userDb)){
                return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'User tidak ditemukan','statusCode'=>404]);
            }
            $userDb = json_decode($userDb, true);
            $upToken1 = function() use ($request, $jwtController, $utilityController, $aesController, $decodedRefresh, $userDb, $next){
                $updated = $jwtController->updateTokenWebsite($decodedRefresh['data']);
                if($updated['status'] == 'error'){
                    return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'Update token error','statusCode'=>500]);
                }
                $request->merge(['user_auth' => [...$userDb, 'number' => $updated['data']]]);
                $response = $next($request);
                setcookie('token1', '', ['expires'  => time() - 3600, ...self::$metaDelCookie]);
                setcookie('token1', $updated['data'], ['expires'  => time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')), ...self::$metaDelCookie]);
                return $response;
            };
            if(!isset($_COOKIE['token1'])){
                return $upToken1();
            }
            $token1 = json_decode($_COOKIE['token1'], true);
            if(!isset($token1['value']) && !isset($token1['exp'])){
                return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'Invalid Token','statusCode'=>500]);
            }
            $delToken1 = $delCookie($token1, 'token1');
            if($delToken1['status'] == 'error'){
                return $this->handleRedirect($request, $utilityController, $aesController, 'error');
            }
            $decoded = $jwtController->decode($request, $utilityController, $token1['value'], 'JWT_SECRET');
            if($decoded['status'] == 'error'){
                if($decoded['message'] == 'Expired token'){
                    return $upToken1();
                }
                return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>$decoded['message'],'statusCode'=>500]);
            }
            $request->merge(['user_auth' => [...$userDb, 'number' => $decoded['data']['number']]]);
            if($currentPath == '/check-auth'){
                return $utilityController->getView($request, $aesController, '', ['data'=>$adminController->getUserAuth($request, app()->make(AdminController::class))], 'json_encrypt');
            }
            return $next($request);
        }else{
            //if cookie gone
            $authPage = ['/dashboard', '/profil', '/event-booked'];
            $prefAuth = ['/admin/'];
            if($isPath($currentPath, $authPage, $prefAuth) || $isPath($currentPath, array_map(fn($path) => '/api' . $path, $authPage), array_map(fn($path) => '/api' . $path, $prefAuth))){
                if(isset($_COOKIE['token1'])){
                    $token1 = json_decode($_COOKIE['token1'], true);
                    if(!isset($token1['value']) && !isset($token1['exp'])){
                        return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'Invalid Token','statusCode'=>400]);
                    }
                    $delToken1 = $delCookie($token1, 'token1');
                    if($delToken1['status'] == 'error'){
                        return $this->handleRedirect($request, $utilityController, $aesController, ['isCondition'=>'error','message'=>'Delete token error','statusCode'=>500]);
                    }
                    $decoded = $jwtController->decode($request, $utilityController, $token1['value'], 'JWT_SECRET');
                    if($decoded['status'] == 'error'){
                        return $this->handleRedirect($request, $utilityController, $aesController, 'error');
                    }
                    $jwtController->deleteRefreshToken($decoded['data']['user'],$decoded['data']['number']);
                    return $this->handleRedirect($request, $utilityController, $aesController, 'error');
                }else{
                    return $this->handleRedirect($request, $utilityController, $aesController, 'error');
                }
            }
            if($currentPath == '/check-auth'){
                return $utilityController->getView($request, $aesController, '', ['data'=>'success public'], 'json_encrypt');
            }
            return $next($request);
        }
    }
}