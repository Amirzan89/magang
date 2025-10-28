<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Security\JWTController;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\UtilityController;
use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
class LoginController extends Controller
{
    public function Login(Request $request, JWTController $jwtController, RefreshToken $refreshToken, AdminController $adminController, AESController $aesController, UtilityController $utilityController){
        $validator = Validator::make($request->only('email','password'), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email harus di isi',
            'email.email' => 'Email yang anda masukkan invalid',
            'password.required' => 'Password harus di isi',
        ]);
        if ($validator->fails()) {
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json_encrypt', 422);
        }
        $email = $request->input("email");
        // $email = "Admin@gmail.com";
        $pass = $request->input("password");
        // $pass = "Admin@1234567890";
        $user = User::select('id_user', 'nama_lengkap', 'jenis_kelamin', 'no_telpon', 'email', 'password', 'foto', 'google_id')->whereRaw("BINARY email = ?",[$email])->first();
        if(is_null($user)){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Email anda salah'], 'json_encrypt', 400);
        }
        if(!password_verify($pass,$user['password'])){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Password anda salah'], 'json_encrypt', 400);
        }
        $jwtData = $jwtController->createJWTWebsite($refreshToken, $utilityController, $user['id_user']);
        if($jwtData['status'] == 'error'){
            return response()->json($jwtData, 400);
        }
        $metaCookie = [
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        unset($user['id_user'], $user['password']);
        $request->merge(['user_auth' => $user]);
        $fotoStore = $adminController->getFotoProfile($request);
        if($fotoStore['status'] == 'error'){
            $user['foto'] = null;
        }else{
            $user['foto'] = $fotoStore['data'];
        }
        setcookie('token1', json_encode(['value' => $jwtData['data']['token'], 'exp' => time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED'))]), [
            'expires' => time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')),
            ...$metaCookie
        ]);
        setcookie('token2', json_encode(['value'=>$jwtData['data']['refresh'], 'exp' => time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED'))]), [
            'expires' => time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED')),
            ...$metaCookie
        ]);
        return $utilityController->getView($request, $aesController, '', ['message'=>'Login sukses silahkan masuk dashboard','data'=>$user], 'json_encrypt');
    }
}
?>