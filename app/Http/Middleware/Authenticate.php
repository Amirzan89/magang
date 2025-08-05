<?php
namespace App\Http\Middleware;
use App\Http\Controllers\Auth\JWTController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use Closure;
class Authenticate
{
    private function handleRedirect($request, $cond, $link = '/login'){
        if($cond == 'error'){
            return $request->wantsJson() ? response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401)->withCookie(Cookie::forget('token1'))->withCookie(Cookie::forget('token2'))->withCookie(Cookie::forget('token3')) : redirect('/login')->withCookies([Cookie::forget('token1'),Cookie::forget('token2'),Cookie::forget('token3')]);
        }
        return $request->wantsJson() ? response()->json(['status' => 'error', 'message' => 'redirect', 'link' => $link], 302) : redirect($link);
    }
    public function handle(Request $request, Closure $next){
        $jwtController = app()->make(JWTController::class);
        $currentPath = $request->getPathInfo();
        $previousUrl = url()->previous();
        $path = parse_url($previousUrl, PHP_URL_PATH);
        if($request->hasCookie("token1") && $request->hasCookie("token2") && $request->hasCookie("token3")){
            $token1 = $request->cookie('token1');
            $token2 = $request->cookie('token2');
            $token3 = $request->cookie('token3');
            $tokenDecode1 = json_decode(base64_decode($token1),true);
            $email = $tokenDecode1['email'];
            $number = $tokenDecode1['number'];
            $publicPage = ['/', '/login', '/register', '/password/reset', '/verify/password', '/verify/email', '/auth/redirect', '/auth/google'];
            $prefPublic = ['/event'];
            if((in_array($currentPath, $publicPage) || !empty(array_filter($prefPublic, fn($prefix) => strpos($currentPath, $prefix) === 0))) && $request->isMethod('get')){
                if(in_array(ltrim($path), $publicPage)){
                    $response = $this->handleRedirect($request, 'success', '/page/dashboard');
                }else{
                    $prefixes = ['/admin/download'];
                    $response = null;
                    foreach ($prefixes as $prefix) {
                        if ($prefix !== '' && strpos($path, $prefix) === 0) {
                            $response = $this->handleRedirect($request, 'success', '/page/dashboard');
                        }
                    }
                    if (is_null($response) && $request->isMethod('get') && !in_array($path, ['/download'])) {
                        $response = $this->handleRedirect($request, 'success', $path ?? '/page/dashboard');
                    }
                }
                $cookies = $response->headers->getCookies();
                foreach ($cookies as $cookie) {
                    if ($cookie->getName() === 'token1') {
                        $expiryTime = $cookie->getExpiresTime();
                        $currentTime = time();
                        if ($expiryTime && $expiryTime < $currentTime) {
                            $response->withCookie(Cookie::forget('token1'));
                            $response->withCookie(Cookie::forget('token2'));
                        }
                    } else if ($cookie->getName() === 'token3') {
                        $expiryTime = $cookie->getExpiresTime();
                        $currentTime = time();
                        if ($expiryTime && $expiryTime < $currentTime) {
                            $response->withCookie(Cookie::forget('token3'));
                        }
                    }
                }
                return $response;
            }
            $decode = [
                'email'=>$email,
                'token'=>$token2,
                'opt'=>'token'
            ];
            $decodeRefresh = [
                'email'=>$email,
                'token'=>$token3,
                'opt'=>'refresh'
            ];
            //check user is exist in database
            if(!User::select('email')->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
                return $this->handleRedirect($request, 'error');
            }
            //check token if exist in database
            if(!$jwtController->checkExistRefreshToken($token3, 'website')){
                //if token is not exist in database
                $delete = $jwtController->deleteRefreshToken($email,$number, 'website');
                if($delete['status'] == 'error'){
                    return $this->handleRedirect($request, 'error');
                }
                return $this->handleRedirect($request, 'error');
            }
            //if token exist
            $decodedRefresh = $jwtController->decode($decodeRefresh);
            if($decodedRefresh['status'] == 'error'){
                if($decodedRefresh['message'] == 'Expired token'){
                    return $this->handleRedirect($request, 'error');
                }else if($decodedRefresh['message'] == 'invalid email'){
                    return $this->handleRedirect($request, 'error');
                }
                return $this->handleRedirect($request, 'error');
            }
            //if token refresh success decoded and not expired
            $decoded = $jwtController->decode($decode);
            if($decoded['status'] == 'error'){
                if($decoded['message'] == 'Expired token'){
                    $updated = $jwtController->updateTokenWebsite($decodedRefresh['data']);
                    if($updated['status'] == 'error'){
                        return response()->json(['status'=>'error','message'=>'update token error'],500);
                    }
                    //when working using this
                    $userAuth = $decodedRefresh['data'];
                    $userAuth['number'] = $decodedRefresh['data']['number'];
                    unset($decodedRefresh);
                    $request->merge(['user_auth' => $userAuth]);
                    $response = $next($request);
                    $cookies = $response->headers->getCookies();
                    foreach ($cookies as $cookie) {
                        if ($cookie->getName() === 'token1') {
                            $response->cookie('token1',$token1,$cookie->getExpiresTime());
                        }else if ($cookie->getName() === 'token3') {
                            $response->cookie('token3',$token3,$cookie->getExpiresTime());
                        }
                    }
                    Cookie::forget('token2');
                    $response->cookie('token2', $updated['data'], time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')));
                    return $response;
                    //when error using this
                    // $userAuth = $decoded['data'];
                    // $userAuth['number'] = $decoded['data']['number'];
                    // unset($decoded);
                    // $request->merge(['user_auth'=>$userAuth]);
                    // return $next($request);
                }
                return response()->json(['status'=>'error','message'=>$decoded['message']],500);
            }
            //if success decode
            //when working using this
            $userAuth = $decoded['data'];
            $userAuth['number'] = $decoded['data']['number'];
            unset($decoded);
            $request->merge(['user_auth' => $userAuth]);
            $response = $next($request);
            return $response;
            //when error using this
            // $userAuth = $decoded['data'];
            // $userAuth['number'] = $decoded['data']['number'];
            // unset($decoded);
            // $request->merge(['user_auth'=>$userAuth]);
            // return $next($request);
        }else{
            //if cookie gone
            $page = ['/page/dashboard', '/profil', '/admin', '/admin/tambah', '/rekap', '/rekap/tambah'];
            $pagePrefix = ['/admin', '/rekap'];
            if(Str::startsWith($currentPath, $pagePrefix) || in_array($currentPath,$page)){
                if($request->hasCookie("token1")){
                    $token1 = json_decode(base64_decode($request->cookie('token1')),true);
                    $email = $token1['email'];
                    $number = $token1['number'];
                    $delete = $jwtController->deleteRefreshToken($email,$number, 'website');
                    if($delete['status'] == 'error'){
                        return response()->json(['status'=>'error','message'=>'delete token error'],500);
                    }else{
                        return $this->handleRedirect($request, 'error');
                    }
                }else{
                    return $this->handleRedirect($request, 'error');
                }
            }
            return $next($request);
        }
    }
}