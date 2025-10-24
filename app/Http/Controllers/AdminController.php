<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Security\JWTController;
use App\Http\Controllers\Security\AESController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
class AdminController extends Controller
{
    private static $adminRole = [];
    public function __construct(){
        self::$adminRole = ['super admin', 'admin'];
    }
    public function getFotoProfile(Request $request, AESController $aesController){
        $userAuth = $request->input('user_auth');
        $referrer = $request->headers->get('referer');
        if(!$referrer && $request->path() == 'admin/download/foto'){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Invalid URL Foto Profile'], $request->input('key'), $request->input('iv'))], 400);
        }
        if(empty($userAuth['foto']) || is_null($userAuth['foto'])){
            $defaultPhotoPath = 'admin/default.jpg';
            return response()->download(storage_path('app/' . $defaultPhotoPath), 'foto.' . pathinfo($defaultPhotoPath, PATHINFO_EXTENSION));
        }else{
            $filePath = storage_path('app/admin/foto' . $userAuth['foto']);
            if(empty($userAuth['foto'] || is_null($userAuth['foto'])) || !file_exists($filePath) || !is_file($filePath)){
                return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Foto Profile tidak ditemukan'], $request->input('key'), $request->input('iv'))], 400);
            }
            return response()->json(['status'=>'success','message'=>$aesController->encryptResponse(['message'=>'Foto Profile ditemukan','data'=>Crypt::decrypt(file_get_contents($filePath))], $request->input('key'), $request->input('iv'))]);
        }
    }
    public function updateProfile(Request $request, JWTController $jwtController, AESController $aesController){
        $validator = Validator::make($request->only('email_new', 'nama_lengkap', 'jenis_kelamin', 'no_telpon', 'foto'), [
            'email_new'=>'nullable|email',
            'nama_lengkap' => 'required|max:50',
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
            'no_telpon' => 'required|digits_between:11,13',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ],[
            'email_new.email'=>'Email yang anda masukkan invalid',
            'nama_lengkap.required' => 'Nama admin wajib di isi',
            'nama_lengkap.max' => 'Nama admin maksimal 50 karakter',
            'jenis_kelamin.required' => 'Jenis kelamin wajib di isi',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan',
            'no_telpon.required' => 'Nomor telepon wajib di isi',
            'no_telpon.digits_between' => 'Nomor telepon tidak boleh lebih dari 13 karakter',
            'foto.image' => 'Foto Admin harus berupa gambar',
            'foto.mimes' => 'Format foto admin tidak valid. Gunakan format jpeg, png, jpg',
            'foto.max' => 'Ukuran foto admin tidak boleh lebih dari 5MB',
        ]);
        if ($validator->fails()){
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages){
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>implode(', ', $errors)], $request->input('key'), $request->input('iv'))], 400);
        }
        $userAuth = $request->input('user_auth');
        $profile = User::select('auth.id_auth', 'auth.password', 'admin.foto')->where('auth.id_auth',$userAuth['id_auth'])->join('auth', 'admin.id_auth', '=', 'auth.id_auth')->firstOrFail();
        if(!is_null($request->input('email') || !empty($request->input('email'))) && $request->input('email') != $userAuth['email'] && User::whereRaw("BINARY email = ?",[$request->input('email')])->exists()){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Email sudah digunakan'], $request->input('key'), $request->input('iv'))], 400);
        }
        $updatedAuthProfile = User::where('id_auth',$userAuth['id_auth'])->update([
            'email'=>(is_null($request->input('email')) || empty($request->input('email'))) ? $userAuth['email'] : $request->input('email'),
        ]);
        $updateProfile = User::where('id_auth',$userAuth['id_auth'])->update([
            'nama_admin'=>$request->input('nama_admin'),
        ]);
        if(!$updatedAuthProfile || !$updateProfile){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Gagal memperbarui profile'], $request->input('key'), $request->input('iv'))], 500);
        }
        $updated = $jwtController->updateJWTProfile();
        if($updated['status'] == 'error'){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'update token error'], $request->input('key'), $request->input('iv'))], 500);
        }
        //get exppp
        //
        /////
        setcookie('token1', '', ['expires'  => time() - 3600, ...self::$metaDelCookie]);
        setcookie('token2', '', ['expires'  => time() - 3600, ...self::$metaDelCookie]);
        setcookie('token1', $updated['data']['token'], ['expires'  => time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')), ...self::$metaDelCookie]);
        setcookie('token2', $updated['data']['refresh'], ['expires'  => time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED')), ...self::$metaDelCookie]);
        return response()->json(['status'=>'success','message'=>$aesController->encryptResponse(['message'=>'Profile Anda Berhasi di perbarui'], $request->input('key'), $request->input('iv'))]);
    }
    public function updatePassword(Request $request, AESController $aesController){
        $validator = Validator::make($request->only('password_old', 'password', 'password_confirm'), [
            'password_old' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\p{P}\p{S}])[\p{L}\p{N}\p{P}\p{S}]+$/u',
            ],
            'password_confirm' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\p{P}\p{S}])[\p{L}\p{N}\p{P}\p{S}]+$/u',
            ],
        ],[
            'password_old.required'=>'Password lama wajib di isi',
            'password.required'=>'Password wajib di isi',
            'password.min'=>'Password minimal 8 karakter',
            'password.max'=>'Password maksimal 25 karakter',
            'password.regex'=>'Password terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
            'password_confirm.required'=>'Password konfirmasi harus di isi',
            'password_confirm.min'=>'Password konfirmasi minimal 8 karakter',
            'password_confirm.max'=>'Password konfirmasi maksimal 25 karakter',
            'password_confirm.regex'=>'Password konfirmasi terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
        ]);
        if ($validator->fails()){
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages){
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>implode(', ', $errors)], $request->input('key'), $request->input('iv'))], 400);
        }
        $userAuth = $request->input('user_auth');
        $passOld = $request->input('password_old');
        $pass = $request->input('password');
        $passConfirm = $request->input('password_confirm');
        if($pass !== $passConfirm){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Password Harus Sama'], $request->input('key'), $request->input('iv'))],400);
        }
        $profileDb = User::select('password')->where('id_user',$userAuth['id_user'])->first();
        if(is_null($profileDb)){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'User Tidak Ditemukan'], $request->input('key'), $request->input('iv'))], 404);
        }
        if(!password_verify($passOld,$profileDb->password)){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Password salah'], $request->input('key'), $request->input('iv'))],400);
        }
        $updatePassword = User::where('id_user',$userAuth['id_user'])->update([
            'password' => Hash::make($pass),
        ]);
        if(!$updatePassword){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Gagal memperbarui password profile'], $request->input('key'), $request->input('iv'))], 500);
        }
        return response()->json(['status'=>'success','message'=>$aesController->encryptResponse(['message'=>'Password profile berhasil di perbarui'], $request->input('key'), $request->input('iv'))]);
    }
    public function logout(Request $request, JWTController $jwtController, AESController $aesController){
        $jwtController->deleteRefreshToken($request->input('user_auth')['id_user'],$request->input('user_auth')['number']);
        $metaCookie = [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => null,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        setcookie('token1', '', $metaCookie);
        setcookie('token2', '', $metaCookie);
        setcookie('token3', '', $metaCookie);
        return response()->json(['status'=>'success','message'=>$aesController->encryptResponse(['message'=>'Logout berhasil silahkan login kembali'], $request->input('key'), $request->input('iv'))]);
    }
}
?>