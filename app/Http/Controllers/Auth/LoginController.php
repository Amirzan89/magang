<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\JWTController;
use App\Http\Controllers\UtilityController;
use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
class LoginController extends Controller
{
    private static $baseURL;
    public function __construct(){
        self::$baseURL = env('FRONTEND_URL', 'locahost:3000');
    }
    public function Login(Request $request, RecaptchaController $recaptchaController, JWTController $jwtController, RefreshToken $refreshToken){
        $validator = Validator::make($request->only('email','password', 'recaptcha'), [
            'email' => 'required|email',
            'password' => 'required',
            'recaptcha' => 'required',
        ], [
            'email.required' => 'Email harus di isi',
            'email.email' => 'Email yang anda masukkan invalid',
            'password.required' => 'Password harus di isi',
            'recaptcha.required' => 'Recaptcha harus di isi',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        $recaptcha = $recaptchaController->verify($request->input('recaptcha'));
        if($recaptcha['status'] == 'error'){
            return response()->json($recaptcha, 400);
        }
        $email = $request->input("email");
        // $email = "Admin@gmail.com";
        $pass = $request->input("password");
        // $pass = "Admin@1234567890";
        $user = User::select('password')->whereRaw("BINARY email = ?",[$email])->first();
        if (is_null($user)) {
            return response()->json(['status' => 'error', 'message' => 'Email salah'], 400);
        }
        if(!password_verify($pass,$user['password'])){
            return response()->json(['status'=>'error','message'=>'Password salah'],400);
        }
        $jwtData = $jwtController->createJWTWebsite($email,$refreshToken);
        if($jwtData['status'] == 'error'){
            return response()->json($jwtData, 400);
        }
        $data1 = ['email'=>$email,'number'=>$jwtData['number']];
        return response()->json(['status'=>'success','message'=>'login sukses silahkan masuk dashboard'])
        ->cookie('token1',base64_encode(json_encode($data1)),time()+intval(env('JWT_ACCESS_TOKEN_EXPIRED')))
        ->cookie('token2',$jwtData['data']['token'],time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')))
        ->cookie('token3',$jwtData['data']['refresh'],time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED')));
    }
    public function redirectToProvider(){
        return Socialite::driver('google')->redirect();
    }
    public function handleGoogleLogin(Request $request, JWTController $jwtController, RefreshToken $refreshToken){
        $cosRes = function($path, $data = null, $code = 200){
            if($path == '/auth/google'){
                return UtilityController::getView('dashboard', [], ['redirect' => '/dashboard']);
            }else if($path == '/auth/google-tap'){
                return response()->json($data, $code);
            }
        };
        $result = null;
        if('/' . $request->path() == '/auth/google'){
            $result = ((array)Socialite::driver('google')->stateless()->user())['user'];
        }else if('/' . $request->path() == '/auth/google-tap'){
            $validator = Validator::make($request->only('credential'), [
                'credential' => 'required',
            ], [
                'credential.required' => 'Credential wajib di isi',
            ]);
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                    $errors[$field] = $errorMessages[0];
                    break;
                }
                return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
            }
            $result = $jwtController->decodeGoogleLogin($request->input('credential'));
            if($result['status'] == 'error'){
                return response()->json(['status'=>'error','message'=>$result['message']], 400);
            }
            $result = (array)$result['data'];
        }else{
            return response()->json(['status'=>'error','message'=> 'Invalid path'], 400);
        }
        if(User::select('email')->whereRaw("BINARY email = ?",[$result['email']])->limit(1)->exists()){
            if($request->hasCookie("token1") && $request->hasCookie("token2") && $request->hasCookie("token3")){
                $token1 = $request->cookie('token1');
                $token2 = $request->cookie('token2');
                $req = [
                    'email'=>json_decode(base64_decode($token1), true)['email'],
                    'token'=>$token2,
                    'opt'=>'token',
                ];
                $decoded = $jwtController->decode($req);
                if($decoded['status'] == 'error'){
                    if($decoded['message'] == 'Expired token'){
                        $updated = $jwtController->updateTokenWebsite($decoded);
                        if($updated['status'] == 'error'){
                            return $cosRes('/' . $request->path(), $updated, 500);
                        }
                        return $cosRes('/' . $request->path(), ['status'=>'success', 'message'=>'success login redirect to dashboard'])
                        ->cookie('token1',$token1,time()+intval(env('JWT_ACCESS_TOKEN_EXPIRED')))
                        ->cookie('token2',$updated['data'],time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')));
                    }
                    return $cosRes('/' . $request->path(), $decoded, 400);
                }
                return $cosRes('/' . $request->path(), ['status'=>'success', 'message'=>'success login redirect to dashboard']);
                //if user exist in database and doesnt login
            }else{
                if(User::select('name')->whereRaw("BINARY email = ? AND email_verified = 0",[$result['email']])->limit(1)->exists()){
                    DB::table('users')->whereRaw("BINARY email = ?",[$result['email']])->update(['email_verified'=>true]);
                }
                $jwtData = $jwtController->createJWTWebsite($result['email'],$refreshToken);
                if($jwtData['status'] == 'error'){
                    return $cosRes('/' . $request->path(), $jwtData, 500);
                }
                $encoded = base64_encode(json_encode(['email'=>$result['email'], 'number'=>$jwtData['number']]));
                return $cosRes('/' . $request->path(), ['status'=>'success', 'message'=>'success login redirect to dashboard', 'data'=>'/dashboard'])
                ->cookie('token1',$encoded,time()+intval(env('JWT_ACCESS_TOKEN_EXPIRED')))
                ->cookie('token2',$jwtData['data']['token'],time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')))
                ->cookie('token3',$jwtData['data']['refresh'],time()+intval('JWT_REFRESH_TOKEN_EXPIRED'));
            }
        //if user dont exist in database
        }else{
            return UtilityController::getView('forgotPassword', ['email'=>$result['email'], 'nama'=>$result['name'], 'file'=>$result['picture'], 'url'=>'/auth/google'], '/' . $request->path() == '/auth/google' ? ['cond' => ['view', 'redirect'], 'redirect' => '/auth/google'] : 'only_cookie');
        }
    }
}
?>