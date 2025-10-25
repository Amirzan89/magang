<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Security\JWTController;
use App\Http\Controllers\Security\AESController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
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
    public function fetchFotoProfile(Request $request, AESController $aesController){
        $fotoProfile = self::getFotoProfile($request);
        if($fotoProfile['status'] == 'error'){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>$fotoProfile['message']],$request->input('key'), $request->input('iv'))], $fotoProfile['statusCode']);
        }
        return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['data'=>$fotoProfile['data']],$request->input('key'), $request->input('iv'))], $fotoProfile['statusCode']);
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
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'],$request->input('key'), $request->input('iv'))], 422);
        }
        $userAuth = $request->input('user_auth');
        if((!is_null($request->input('email_new')) && !empty($request->input('email_new'))) && ($request->input('email_new') != $userAuth['email']) && User::whereRaw("BINARY email = ?",[$request->input('email_new')])->exists()){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Email sudah digunakan'], $request->input('key'), $request->input('iv'))], 400);
        }
        $file = $utilityController->base64File($request);
        if($file){
            if(!($file instanceof \Illuminate\Http\UploadedFile)){
                return response()->json(['status' => 'error','message'=>$aesController->encryptResponse(['message'=>'File foto tidak valid'], $request->input('key'), $request->input('iv'))], 400);
            }
            if(!in_array($file->extension(), ['jpeg', 'png', 'jpg'])){
                return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Format Foto tidak valid. Gunakan format jpeg, png, jpg'],$request->input('key'), $request->input('iv'))], 400);
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
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>'Gagal memperbarui profile'], $request->input('key'), $request->input('iv'))], 500);
        }
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
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'],$request->input('key'), $request->input('iv'))], 422);
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