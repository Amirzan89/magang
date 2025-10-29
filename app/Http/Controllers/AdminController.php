<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\MailController;
use App\Http\Controllers\Security\JWTController;
use App\Http\Controllers\Security\AESController;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Models\Verifikasi;
use App\Models\User;
use Carbon\Carbon;
class AdminController extends Controller
{
    private static $metaDelCookie;
    public function __construct(){
        self::$metaDelCookie = [
            'path'     => '/',
            'domain'   => null,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
    }
    public static function getFotoProfile(Request $request){
        $userAuth = $request->input('user_auth');
        $referrer = $request->headers->get('referer');
        if(!$referrer && $request->path() == 'api/admin/download/foto-profile'){
            return ['status'=>'error','message'=>'Invalid URL Foto Profile','statusCode'=>400];
        }
        if(!empty($userAuth['google_id']) && str_starts_with($userAuth['foto'], 'https://lh3.googleusercontent.com')){
            return ['status' => 'success', 'data' => $userAuth['foto']];
        }
        if(empty($userAuth['foto']) || is_null($userAuth['foto'])){
            $defaultPhotoPath = 'admin/default.jpg';
            $fullPath = storage_path('app/' . $defaultPhotoPath);
            if(!file_exists($fullPath)){
                return ['status' => 'error', 'message' => 'Default Foto Profile tidak ditemukan', 'statusCode' => 404];
            }
            $fileContent = file_get_contents(storage_path('app/' . $defaultPhotoPath));
            return [
                'status' => 'success',
                'data' => [
                    'data' => base64_encode($fileContent),
                    'meta' =>[
                        'filename' => 'default.jpg',
                        'size' => filesize($fullPath),
                        'type' => mime_content_type($fullPath),
                    ]
                ]
            ];
        }else{
            $filePath = storage_path('app/admin/' . $userAuth['foto']);
            if(empty($userAuth['foto'] || is_null($userAuth['foto'])) || !file_exists($filePath) || !is_file($filePath)){
                return ['status'=>'error','message'=>'Foto Profile tidak ditemukan','statusCode'=>404];
            }
            $fileContent = Crypt::decrypt(file_get_contents($filePath));
            return [
                'status' => 'success',
                'data' => [
                    'data' => base64_encode($fileContent),
                    'meta' => [
                        'filename' => 'default.jpg',
                        'size' => filesize($filePath),
                        'type' => finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $fileContent),
                    ]
                ]
            ];
        }
    }
    public function fetchFotoProfile(Request $request, UtilityController $utilityController, AESController $aesController){
        $fotoProfile = self::getFotoProfile($request);
        if($fotoProfile['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$fotoProfile['message']], 'json_encrypt', $fotoProfile['statusCode']);
        }
        return $utilityController->getView($request, $aesController, '', ['data'=>$fotoProfile['data']], 'json_encrypt');
    }
    public function getChangePass(Request $request, UtilityController $utilityController, AESController $aesController, $any = null){
        if(Str::startsWith($request->path(), 'verify/password') && $request->isMethod('get')){
            if(!$request->hasValidSignature()){
                return $utilityController->getView($request, $aesController, '', ['message' => 'Link invalid or expired'], ['cond' => ['view', 'redirect'], 'redirect' => '/forgot-password'], 400);
            }
            $email = $request->query('email');
            if(!Verifikasi::whereRaw("BINARY link = ?", [$any])->exists()){
                return $utilityController->getView($request, $aesController, '', ['message'=>'Link invalid'], ['cond'=>['view','redirect'], 'redirect'=>'/forgot-password'], 400);
            }
            if (!Verifikasi::whereRaw("BINARY email = ?", [$email])->exists()){
                return $utilityController->getView($request, $aesController, '', ['message'=>'Email invalid'], ['cond'=>['view','redirect'], 'redirect'=>'/forgot-password'], 400);
            }
            if(!Verifikasi::whereRaw("BINARY email = ? AND BINARY link = ?", [$email, $any])->exists()){
                return $utilityController->getView($request, $aesController, '', ['message'=>'Link invalid'], ['cond'=>['view','redirect'], 'redirect'=>'/forgot-password'], 400);
            }
            if(!Verifikasi::whereRaw("BINARY email = ?", [$email])->where('updated_at', '>=', now()->subMinutes(15))->exists()){
                // Verifikasi::whereRaw("BINARY email = ? AND deskripsi = 'password'", [$email])->delete();
                return $utilityController->getView($request, $aesController, '', ['message' => 'Link expired'], ['cond' => ['view','redirect'], 'redirect' => '/forgot-password'], 400);
            }
            return $utilityController->getView($request, $aesController, '',['data'=>[
                'email' => $email,
                'title' => 'Reset Password',
                'link'  => $any,
                'otp'   => '',
                'div'   => 'verifyDiv',
                'description' => 'password'
            ]], ['cond'=>['view','redirect'], 'redirect'=>'/forgot-password'], 400);
        }
        $validator = Validator::make($request->only('email', 'otp'), [
            'email'=>'required|email',
            'otp' =>'required'
        ],[
            'email.required'=>'Email harus di isi',
            'email.email'=>'Email yang anda masukkan invalid',
            'otp.required'=>'OTP harus di isi',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json', 422);
        }
        $user = User::select('id_user')->whereRaw("BINARY email = ?",[$request->input('email')])->first();
        if(is_null($user)){
            return response()->json(['status'=>'error','message'=>'Email tidak terdaftar !'],400);
        }
        $email = $request->input('email');
        $otp = $request->input('otp');
        if(!Verifikasi::whereRaw("BINARY email = ?", [$email])->exists()){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Email invalid'], 'json', 400);
        }
        if(!Verifikasi::whereRaw("BINARY email = ? AND BINARY kode_otp = ?", [$email, $otp])->exists()){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Kode OTP invalid'], 'json', 400);
        }
        if(!Verifikasi::whereRaw("BINARY email = ?", [$email])->where('updated_at', '>=', now()->subMinutes(15))->exists()){
            // Verfikasi::whereRaw("BINARY email = ? AND deskripsi = 'password'", [$email])->delete();
            return $utilityController->getView($request, $aesController, '', ['message'=>'Kode OTP expired'], 'json', 400);
        }
        return $utilityController->getView($request, $aesController, '', ['message'=>'OTP Anda benar, silahkan ganti password'], 'json');
    }
    public function changePassEmail(Request $request, JWTController $jwtController, RefreshToken $refreshToken, UtilityController $utilityController, AESController $aesController){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => [
                'required', 'string', 'min:8', 'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'password_confirm' => [
                'required', 'string', 'min:8', 'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'otp' => 'nullable',
            'link' => 'nullable',
        ], [
            'email.required' => 'Email wajib di isi',
            'email.email' => 'Email yang anda masukkan invalid',
            'password.required' => 'Password wajib di isi',
            'password.min' => 'Password minimal 8 karakter',
            'password.max' => 'Password maksimal 25 karakter',
            'password.regex' => 'Password baru wajib terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
            'password_confirm.required' => 'Password konfirmasi konfirmasi harus di isi',
            'password_confirm.min' => 'Password konfirmasi minimal 8 karakter',
            'password_confirm.max' => 'Password konfirmasi maksimal 25 karakter',
            'password_confirm.regex' => 'Password konfirmasi terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message' => $firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json', 422);
        }
        $email = $request->input('email');
        $password = $request->input('password');
        $passwordConfirm = $request->input('password_confirm');
        $otp = $request->input('otp');
        $link = $request->input('link');
        $user = User::select('nama_lengkap')->whereRaw("BINARY email = ?", [$email])->first();
        if(is_null($user)){
            return $utilityController->getView($request, $aesController, '', ['message' => 'Email tidak terdaftar !'], 'json', 400);
        }
        $verify = Verifikasi::whereRaw("BINARY email = ?", [$email])->where('deskripsi', 'password')->first();
        if(is_null($verify)){
            return $utilityController->getView($request, $aesController, '', ['message' => 'Email invalid'], 'json', 400);
        }
        if($password !== $passwordConfirm){
            return $utilityController->getView($request, $aesController, '', ['message' => 'Password harus Sama'], 'json', 400);
        }
        $expTime = MailController::getConditionOTP()[($verify->send - 1)] ?? 5;
        $diffMinutes = Carbon::parse($verify->updated_at)->diffInMinutes(Carbon::now());
        if($diffMinutes >= $expTime){
            return $utilityController->getView($request, $aesController, '', ['message' => (empty($link) ? 'Token' : 'Link') . ' expired'], 'json', 400);
        }
        if(is_null($link) || empty($link)){
            if($verify->kode_otp !== $otp){
                return $utilityController->getView($request, $aesController, '', ['message' => 'Kode OTP invalid'], 'json', 400);
            }
        }else{
            if($verify->link !== $link){
                return $utilityController->getView($request, $aesController, '', ['message' => 'Link invalid'], 'json', 400);
            }
        }
        $updated = User::whereRaw("BINARY email = ?", [$email])->update(['password' => Hash::make($password)]);
        if(!$updated){
            return $utilityController->getView($request, $aesController, '', ['message' => 'error update password'], 'json', 500);
        }
        Verifikasi::whereRaw("BINARY email = ?", [$email])->where('deskripsi', 'password')->delete();
        return $utilityController->getView($request, $aesController, '', ['message' => 'Ganti password berhasil, silahkan login'], 'json');
    }
    public function updateProfile(Request $request, JWTController $jwtController, AESController $aesController, UtilityController $utilityController){
        $validator = Validator::make($request->only('email_new', 'nama_lengkap', 'jenis_kelamin', 'no_telpon', 'foto'), [
            'email_new'=>'nullable|email',
            'nama_lengkap' => 'required|max:50',
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
            'no_telpon' => 'required|digits_between:11,13',
        ],[
            'email_new.email'=>'Email yang anda masukkan invalid',
            'nama_lengkap.required' => 'Nama admin wajib di isi',
            'nama_lengkap.max' => 'Nama admin maksimal 50 karakter',
            'jenis_kelamin.required' => 'Jenis kelamin wajib di isi',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan',
            'no_telpon.required' => 'Nomor telepon wajib di isi',
            'no_telpon.digits_between' => 'Nomor telepon tidak boleh lebih dari 13 karakter',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json_encrypt', 422);
        }
        $userAuth = $request->input('user_auth');
        if((!is_null($request->input('email_new')) && !empty($request->input('email_new'))) && ($request->input('email_new') != $userAuth['email']) && User::whereRaw("BINARY email = ?",[$request->input('email_new')])->exists()){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Email sudah digunakan'], 'json_encrypt', 400);
        }
        $file = $utilityController->base64File($request);
        if($file){
            if(!($file instanceof \Illuminate\Http\UploadedFile)){
                return $utilityController->getView($request, $aesController, '', ['message'=>'File foto tidak valid'], 'json_encrypt', 400);
            }
            if(!in_array($file->extension(), ['jpeg', 'png', 'jpg'])){
                return $utilityController->getView($request, $aesController, '', ['message'=>'Format Foto tidak valid. Gunakan format jpeg, png, jpg'], 'json_encrypt', 400);
            }
            $destinationPath = storage_path('app/admin/');
            $fileToDelete = $destinationPath . $userAuth['foto'];
            if(file_exists($fileToDelete) && !is_dir($fileToDelete)){
                unlink($fileToDelete);
            }
            Storage::disk('admin')->delete($userAuth['foto']);
            $fotoName = $file->hashName();
            $fileData = Crypt::encrypt(file_get_contents($file));
            Storage::disk('admin')->put($fotoName, $fileData);
        }
        $updatedProfile = User::where('id_user',$userAuth['id_user'])->update([
            'email'=>(is_null($request->input('email_new')) || empty($request->input('email_new'))) ? $userAuth['email'] : $request->input('email_new'),
            'nama_lengkap'=>$request->input('nama_lengkap'),
            'jenis_kelamin'=>$request->input('jenis_kelamin'),
            'no_telpon'=>$request->input('no_telpon'),
            'foto' => $file ? $fotoName : $userAuth['foto'],
            'updated_at'=> Carbon::now()
        ]);
        if(!$updatedProfile){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Gagal memperbarui profile'], 'json_encrypt', 500);
        }
        return $utilityController->getView($request, $aesController, '', ['message'=>'Profile Anda Berhasi di perbarui'], 'json_encrypt');
    }
    public function updatePassword(Request $request, UtilityController $utilityController, AESController $aesController){
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
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json_encrypt', 422);
        }
        $userAuth = $request->input('user_auth');
        $passOld = $request->input('password_old');
        $pass = $request->input('password');
        $passConfirm = $request->input('password_confirm');
        if($pass !== $passConfirm){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Password Harus Sama'], 'json_encrypt', 400);
        }
        $profileDb = User::select('password')->where('id_user',$userAuth['id_user'])->first();
        if(is_null($profileDb)){
            return $utilityController->getView($request, $aesController, '', ['message'=>'User Tidak Ditemukan'], 'json_encrypt', 404);
        }
        if(!password_verify($passOld,$profileDb->password)){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Password Salah'], 'json_encrypt', 400);
        }
        $updatePassword = User::where('id_user',$userAuth['id_user'])->update([
            'password' => Hash::make($pass),
        ]);
        if(!$updatePassword){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Gagal memperbarui password profile'], 'json_encrypt', 500);
        }
        return $utilityController->getView($request, $aesController, '', ['message'=>'Password profile berhasil di perbarui'], 'json_encrypt');
    }
    public function logout(Request $request, JWTController $jwtController, UtilityController $utilityController, AESController $aesController){
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
        return $utilityController->getView($request, $aesController, '', ['message'=>'Logout berhasil silahkan login kembali'], 'json_encrypt');
    }
}
?>