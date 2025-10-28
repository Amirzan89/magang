<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Security\JWTController;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\UtilityController;
use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Crypt;
use Exception;
class GoogleController extends Controller
{
    public function redirectToProvider(Request $request){
        $path = '/' . $request->path();
        $payload = [
            'uuid' => Str::uuid()->toString(),
            'mode' => $path == '/login/google' ? 'login' : 'bind',
            'number' => '/' . $request->path() == '/profile/bind-google' ? $request->input('user_auth')['number'] : null,
            'iat' => time()
        ];
        $encrypted = Crypt::encryptString(json_encode($payload));
        Cache::put('oauth_state_'.$payload['uuid'], $encrypted, now()->addMinutes(5));
        return Socialite::driver('google')->with(['state' => $encrypted])->stateless()->redirect();
    }
    public function handleGoogleCallback(Request $request, JWTController $jwtController, RefreshToken $refreshToken, AESController $aesController, UtilityController $utilityController){
        $finalizeAndRespond = function($userDb, $message, $redirect) use ($request, $jwtController, $refreshToken, $aesController) {
            $jwtData = $jwtController->createJWTWebsite($refreshToken, app(UtilityController::class), $userDb['id_user']);
            if($jwtData['status'] == 'error'){
                return UtilityController::getView($request, $aesController, '', ['message'=>$jwtData['message'],'statusCode'=>500], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
            }
            $metaCookie = [
                'expires' => time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')),
                'path' => '/',
                'domain' => env('SESSION_DOMAIN', null),
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            setcookie('token1', json_encode(['value' => $jwtData['data']['token'], 'exp' => time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED'))]), $metaCookie);
            setcookie('token2', json_encode(['value'=>$jwtData['data']['refresh'], 'exp' => time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED'))]), array_merge($metaCookie, ['expires' => time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED'))]));
            return UtilityController::getView($request, $aesController, '', ['message' => $message], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => $redirect]);
        };
        $rawState = $request->query('state');
        if(!$rawState){
            return UtilityController::getView($request, $aesController, '', ['message'=>'Missing state','statusCode'=>403], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
        }
        try{
            $state = json_decode(Crypt::decryptString($rawState), true);
        }catch(Exception $e){
            return UtilityController::getView($request, $aesController, '', ['message'=>'Invalid state','statusCode'=>403], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
        }
        $cached = Cache::pull('oauth_state_' . ($state['uuid'] ?? ''));
        if(!$cached){
            return UtilityController::getView($request, $aesController, '', ['message'=>'Expired or invalid state','statusCode'=>403], ['redirect' => ($state['redirect'] ?? '/login')]);
        }
        if($cached !== $rawState){
            return UtilityController::getView($request, $aesController, '', ['message'=>'State mismatch','statusCode'=>403], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
        }
        try{
            $googleUser = Socialite::driver('google')->stateless()->user();
        }catch(Exception $e){
            return UtilityController::getView($request, $aesController, '', ['message'=>'Google auth failed','statusCode'=>403], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
        }
        $idToken = property_exists($googleUser, 'idToken') ? $googleUser->idToken : ($googleUser->user['id_token'] ?? null);
        if($idToken){
            $resp = @file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . $idToken);
            $info = $resp ? json_decode($resp, true) : null;
            if(!$info || ($info['aud'] ?? '') !== config('services.google.client_id') || !in_array($info['iss'], ['accounts.google.com','https://accounts.google.com'])){
                return UtilityController::getView($request, $aesController, '', ['message'=>'Invalid ID token','statusCode'=>403], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
            }
            if(empty($info['email_verified'])){
                return UtilityController::getView($request, $aesController, '', ['message'=>'Email not verified','statusCode'=>403], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
            }
        }else{
            if(empty($googleUser->user['email_verified'])){
                return UtilityController::getView($request, $aesController, '', ['message'=>'Email not verified','statusCode'=>403], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
            }
        }
        $userDb = User::select('id_user', 'google_id', 'foto')->whereRaw("BINARY email = ?", [$googleUser->getEmail()])->first();
        if(is_null($userDb)){
            return UtilityController::getView($request, $aesController, '', ['message'=>'Akun tidak ditemukan','statusCode'=>404], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
        }
        if(($state['mode'] ?? '') === 'bind'){
            if($userDb['google_id'] != ''){
                return UtilityController::getView($request, $aesController, '', ['message'=>'Akun sudah dihubungkan ke google','statusCode'=>400], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
            }
            User::whereRaw("BINARY email = ?", [$googleUser->getEmail()])->update([
                'google_id' => $googleUser->getId(),
                'foto' => empty($userDb['foto']) || is_null($userDb['foto']) ? $googleUser->getAvatar() : $userDb['foto'],
            ]);
            return $finalizeAndRespond($userDb, 'Akun anda berhasil dihubungkan ke google', '/profile');
        }
        if(empty($userDb['google_id'])){
            return UtilityController::getView($request, $aesController, '', ['message'=>'Akun anda belum terhubung dengan Google','statusCode'=>404], ['cond'=> ['view', 'redirect', 'isGoogleRedirect'], 'redirect' => '/login']);
        }
        return $finalizeAndRespond($userDb, 'Anda berhasil login', '/login');
    }
}
?>