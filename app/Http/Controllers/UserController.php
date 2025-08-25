<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Auth\JWTController;
use App\Http\Controllers\Services\MailController;
use App\Models\User;
use App\Models\Verify;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
class UserController extends Controller
{
    public function getDefaultFoto(Request $request){
        $referrer = $request->headers->get('referer');
        if (!$referrer && $request->path() == 'public/download/foto') {
            abort(404);
        }
        return response()->download(storage_path('app/' . 'user/default.jpg'), 'foto.' . pathinfo('user/default.jpg', PATHINFO_EXTENSION));
    }
    public function getFotoProfile(Request $request){
        $userAuth = $request->input('user_auth');
        $referrer = $request->headers->get('referer');
        if (!$referrer && $request->path() == 'public/download/foto') {
            abort(404);
        }
        if (empty($userAuth['foto']) || is_null($userAuth['foto'])) {
            $defaultPhotoPath = 'user/default.jpg';
            return response()->download(storage_path('app/' . $defaultPhotoPath), 'foto.' . pathinfo($defaultPhotoPath, PATHINFO_EXTENSION));
        } else {
            $filePath = storage_path('app/user/foto/' . $userAuth['foto']);
            if (!empty($userAuth['foto'] && !is_null($userAuth['foto'])) && file_exists($filePath) && is_file($filePath)) {
                return response(Crypt::decrypt(file_get_contents($filePath)));
            }
            abort(404);
        }
    }
    public static function checkEmail($email){
        $userDB = User::select('id_user', 'role')->whereRaw("BINARY email = ?", [$email])->limit(1)->get();
            if ($userDB->isEmpty()) {
                return ['status'=>'error','message'=>'User not found','code'=>404];
            }
            return ['status'=>'success','data' =>json_decode($userDB, true)[0]];
    }
    public function getChangePass(Request $request, User $user, $any = null){
        $validator = Validator::make($request->only('email', 'code'), [
            'email'=>'required|email',
            'code' =>'nullable'
        ],[
            'email.required'=>'Email harus di isi',
            'email.email'=>'Email yang anda masukkan invalid',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        $prefix = '/verify/password';
        if(Str::startsWith('/' . $request->path(), $prefix) && $request->isMethod('get')){
            $email = $request->query('email');
            if (!Verifikasi::whereRaw("BINARY link = ?", [$any])->exists()) {
                return UtilityController::getView('resetPass', ['status' => 'error', 'message' => 'Link invalid', 'code' => 400], ['cond' => ['view', 'redirect'],'redirect' => $prefix]);
            }
            if (!Verifikasi::whereRaw("BINARY email = ?", [$email])->exists()) {
                return UtilityController::getView('resetPass', ['status' => 'error', 'message' => 'Email invalid', 'code' => 400], ['cond' => ['view', 'redirect'],'redirect' => $prefix]);
            }
            if (!Verifikasi::whereRaw("BINARY email = ? AND BINARY link = ?", [$email, $any])->exists()) {
                return UtilityController::getView('resetPass', ['status' => 'error', 'message' => 'Link invalid', 'code' => 400], ['cond' => ['view', 'redirect'],'redirect' => $prefix]);
            }
            $currentDateTime = Carbon::now();
            if (!Verifikasi::whereRaw("BINARY email = ?", [$email])->where('updated_at', '>=', $currentDateTime->subMinutes(1))->exists()) {
                return UtilityController::getView('resetPass', ['status' => 'error', 'message' => 'Link Expired', 'code' => 400], ['cond' => ['view', 'redirect'],'redirect' => $prefix]);
            }
            return UtilityController::getView('resetPass', ['status' => 'success', 'email' => $email, 'link' => $any], ['cond' => ['view', 'redirect'],'redirect' => $prefix]);
        }else{
            $email = $request->input('email');
            $code = $request->input('otp');
            if (!Verify::whereRaw("BINARY email = ?", [$email])->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Email invalid'], 400);
            }
            if (!Verify::whereRaw("BINARY email = ? AND BINARY code = ?", [$email, $code])->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Code OTP invalid'], 400);
            }
            $currentDateTime = Carbon::now();
            if (!DB::table('verify')->whereRaw("BINARY email = ?", [$email])->where('updated_at', '>=', $currentDateTime->subMinutes(15))->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Code OTP expired'], 400);
            }
            return response()->json(['status' => 'success', 'message' => 'OTP Anda benar, silahkan ganti password']);
        }
    }
    public function changePassEmail(Request $request){
        $validator = Validator::make($request->only('email', 'nama', 'password', 'password_confirm', 'code', 'link'), [
            'email'=>'required|email',
            'nama'=>'nullable',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'password_confirm' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'code' => 'nullable',
            'link' => 'nullable',
        ],[
            'email.required'=>'Email harus di isi',
            'email.email'=>'Email yang anda masukkan invalid',
            'password.required'=>'Password harus di isi',
            'password.min'=>'Password minimal 8 karakter',
            'password.max'=>'Password maksimal 25 karakter',
            'password.regex'=>'Password baru harus terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
            'password_confirm.required'=>'Password konfirmasi konfirmasi harus di isi',
            'password_confirm.min'=>'Password konfirmasi minimal 8 karakter',
            'password_confirm.max'=>'Password konfirmasi maksimal 25 karakter',
            'password_confirm.regex'=>'Password konfirmasi terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        $email = $request->input('email');
        $link = $request->input('link');
        if($request->input("password") !== $request->input("password_confirm")){
            return response()->json(['status'=>'error','message'=>'Password Harus Sama'],400);
        }
        if(is_null($link) || empty($link)){
            $code = $request->input('code');
            if (!Verify::whereRaw("BINARY code = ?",[$code])->exists()) {
                return response()->json(['status'=>'error','message'=>'Code OTP invalid'], 400);
            }
            if (!User::whereRaw("BINARY email = ?",[$email])->exists()) {
                return response()->json(['status'=>'error','message'=>'Invalid Email'], 400);
            }
            if (!Verify::whereRaw("BINARY email = ? AND BINARY code = ?",[$email,$code])->exists()) {
                return response()->json(['status'=>'error','message'=>'Invalid Email'], 400);
            }
            $currentDateTime = Carbon::now();
            if (!DB::table('verify')->whereRaw("BINARY email = ?",[$email])->where('updated_at', '>=', $currentDateTime->subMinutes(15))->exists()) {
                return response()->json(['status'=>'error','message'=>'Code OTP expired'], 400);
            }
            if (DB::table('users')->whereRaw("BINARY email = ?",[$email])->update(['password'=>Hash::make($request->input("password"))]) === 0) {
                return response()->json(['status'=>'error','message'=>'Error updating password'], 500);
            }
            if (!DB::table('verify')->whereRaw("BINARY email = ?",[$email])->delete()) {
                return response()->json(['status'=>'error','message'=>'Error updating password'], 500);
            }
            return response()->json(['status'=>'success','message'=>'ganti password berhasil silahkan login']);
        }else{
            if (!Verify::whereRaw("BINARY link = ? AND description = ?", [$link, 'password'])->exists()) {
                return response()->json(['status'=>'error', 'message'=>'Link expired'], 400);
            }
            if (!User::whereRaw("BINARY email = ?", [$email])->exists()) {
                return response()->json(['status'=>'error', 'message'=>'Invalid Email1'], 400);
            }
            if (!Verify::whereRaw("BINARY email = ? AND BINARY link = ? AND description = ?", [$email, $link, 'password'])->exists()) {
                return response()->json(['status'=>'error', 'message'=>'Email invalid'], 400);
            }
            $currentDateTime = Carbon::now();
            if (!DB::table('verify')->whereRaw("BINARY email = ?", [$email])->where('updated_at', '>=', $currentDateTime->subMinutes(15))->exists()) {
                return response()->json(['status'=>'error', 'message'=>'Link expired'], 400);
            }
            if (DB::table('users')->whereRaw("BINARY email = ?", [$email])->update(['password'=>Hash::make($request->input("password"))]) === 0) {
                return response()->json(['status'=>'error', 'message'=>'Error updating password'], 500);
            }
            if (!DB::table('verify')->whereRaw("BINARY email = ? AND description = ?", [$email, 'password'])->delete()) {
                return response()->json(['status'=>'error', 'message'=>'Error updating password'], 500);
            }
            return response()->json(['status'=>'success', 'message'=>'ganti password berhasil silahkan login']);
        }
    }
    public function verifyEmail(Request $request, User $user, $any = null){
        $validator = Validator::make($request->only('email', 'otp'), [
            'email'=>'required|email',
            'otp' =>'nullable'
        ],[
            'email.required'=>'Email wajib di isi',
            'email.email'=>'Email yang anda masukkan invalid',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        //check email on table user
        $user = User::select('name')->whereRaw("BINARY email = ?",[$request->input('email')])->first();
        if (is_null($user)) {
            return response()->json(['status'=>'error','message'=>'Email tidak terdaftar !'],400);
        }
        $prefix = '/verify/email';
        if(Str::startsWith('/' . $request->path(), $prefix) && $request->isMethod('get')){
            //check if user have create verify email
            $email = $request->query('email');
            $verify = Verify::select('link', 'send', 'updated_at')->whereRaw("BINARY email = ?",[$email])->where('description', 'email')->first();
            if (is_null($verify)) {
                return UtilityController::getView('verifEmail', ['status' => 'error', 'message' => 'Email invalid', 'code' => 400], ['cond' => ['view', 'redirect'], 'redirect' => $prefix]);
            }
            //check link
            if ($verify->link !== $any) {
                return UtilityController::getView('verifEmail', ['status' => 'error', 'message' => 'Link invalid', 'code' => 400], ['cond' => ['view', 'redirect'], 'redirect' => $prefix]);
            }
            //check if mail not expired
            $expTime = MailController::getConditionOTP()[($verify->send - 1)];
            if (Carbon::parse($verify->updated_at)->diffInMinutes(Carbon::now()) > $expTime) {
                return UtilityController::getView('verifEmail', ['status' => 'error', 'message' => 'Link expired', 'code' => 400], ['cond' => ['view', 'redirect'], 'redirect' => $prefix]);
            }
            if(DB::table('users')->whereRaw("BINARY email = ?",[$email])->update(['email_verified'=>true]) === 0){
                return UtilityController::getView('verifEmail', ['status' => 'error', 'message' => 'Error verifikasi Email', 'code' => 500], ['cond' => ['view', 'redirect'], 'redirect' => $prefix]);
            }
            if(!DB::table('verifikasi')->whereRaw("BINARY email = ?",[$email])->delete()){
                return UtilityController::getView('resetPass', ['status' => 'error', 'message' => 'Error verifikasi Email', 'code' => 500], ['cond' => ['view', 'redirect'], 'redirect' => $prefix]);
            }
            return UtilityController::getView('resetPass', ['status' => 'success', 'message' => 'verifikasi email berhasil silahkan login'], ['cond' => ['view', 'redirect'], 'redirect' => $prefix]);
        }else{
            $code = $request->input('otp');
            //check if user have otp verify email
            $verify = Verify::select('code','send','updated_at')->whereRaw("BINARY email = ?",[$request->input('email')])->where('description', 'email')->first();
            if (is_null($verify)) {
                return response()->json(['status'=>'error','message'=>'Email invalid'],400);
            }
            //check code
            if ($verify['code'] != $code) {
                return response()->json(['status'=>'error','message'=>'kode otp invalid'],400);
            }
            //check if mail not expired
            $expTime = MailController::getConditionOTP()[($verify->send - 1)];
            if (Carbon::parse($verify->updated_at)->diffInMinutes(Carbon::now()) > $expTime) {
                return response()->json(['status'=>'error','message'=>'kode otp expired'],400);
            }
            if(DB::table('users')->whereRaw("BINARY email = ?",[$request->input('email')])->update(['email_verified'=>true]) === 0){
                return response()->json(['status'=>'error','message'=>'error verifikasi email'],500);
            }
            if(!DB::table('verify')->whereRaw("BINARY email = ?",[$request->input('email')])->delete()){
                return response()->json(['status'=>'error','message'=>'error verifikasi email'],500);
            }
            return response()->json(['status'=>'success','message'=>'verifikasi email berhasil silahkan login']);
        }
    }
    //register user from google login
    public function createGoogleUser(Request $request, JWTController $jwtController, RefreshToken $refreshToken){
        $validator = Validator::make($request->only('email', 'nama', 'password', 'password_confirm', 'code', 'link'), [
            'email'=>'required|email',
            'nama'=>'nullable',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'password_confirm' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'code' => 'nullable',
            'link' => 'nullable',
        ],[
            'email.required'=>'Email harus di isi',
            'email.email'=>'Email yang anda masukkan invalid',
            'password.required'=>'Password harus di isi',
            'password.min'=>'Password minimal 8 karakter',
            'password.max'=>'Password maksimal 25 karakter',
            'password.regex'=>'Password baru harus terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
            'password_confirm.required'=>'Password konfirmasi konfirmasi harus di isi',
            'password_confirm.min'=>'Password konfirmasi minimal 8 karakter',
            'password_confirm.max'=>'Password konfirmasi maksimal 25 karakter',
            'password_confirm.regex'=>'Password konfirmasi terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        if($request->input("password") !== $request->input("password_confirm")){
            return response()->json(['status'=>'error','message'=>'Password Harus Sama'],400);
        }
        //process file foto
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            if(!($file->isValid() && in_array($file->extension(), ['jpeg', 'png', 'jpg']))){
                return response()->json(['status'=>'error','message'=>'Format Foto tidak valid. Gunakan format jpeg, png, jpg'], 400);
            }
            $fotoName = $file->hashName();
            $fileData = Crypt::encrypt(file_get_contents($file));
            Storage::disk('user')->put('foto/' . $fotoName, $fileData);
        }
        $ins = User::insert([
            'uuid' => Str::uuid(),
            'email' => $request->input('email'),
            'name' => $request->input('nama'),
            'password' => Hash::make($request->input('password')),
            'email_verified' => true,
            'foto' => $request->hasFile('foto') ? $fotoName : '',
            'role' => 'user',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        if(!$ins){
            return ['status'=>'error','message'=>'Akun Gagal Dibuat'];
        }
        $data = $jwtController->createJWTWebsite($request->input('email'),$refreshToken);
        if (!$data || $data['status'] == 'error') {
            $errorMessage = $data ? $data['message'] : 'create token error';
            return response()->json(['status' => 'error', 'message' => $errorMessage], 500);
        }
        return response()->json(['status'=>'success','message'=>'login sukses silahkan masuk dashboard'])
        ->cookie('token1',base64_encode(json_encode(['email'=>$request->input('email'),'number'=>$data['number']])),time()+intval(env('JWT_ACCESS_TOKEN_EXPIRED')),'/','',true)
        ->cookie('token2',$data['data']['token'],time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')),'/','',true)
        ->cookie('token3',$data['data']['refresh'],time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED')),'/','',true);
    }
    public function createUser(Request $request, MailController $mailController, Verify $verify){
        $validator = Validator::make($request->only('nama','jenis_kelamin','no_telpon','tanggal_lahir','tempat_lahir','email','password','foto'), [
            'nama'=>'required',
            'jenis_kelamin' => 'nullable|in:laki-laki,perempuan',
            'no_telpon' => 'nullable|digits_between:10,13',
            'email'=>'required | email',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\p{P}\p{S}])[\p{L}\p{N}\p{P}\p{S}]+$/u',
            ],
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ],[
            'nama.required'=>'Nama Lengkap wajib di isi',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan',
            'no_telpon.digits_between' => 'Nomor telepon tidak boleh lebih dari 13 karakter',
            'email.required'=>'Email wajib di isi',
            'email.email'=>'Email yang anda masukkan invalid',
            'password.required'=>'Password wajib di isi',
            'password.min'=>'Password minimal 8 karakter',
            'password.max'=>'Password maksimal 25 karakter',
            'password.regex'=>'Password baru wajib terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
            'foto.image' => 'Foto harus berupa gambar',
            'foto.mimes' => 'Format foto tidak valid. Gunakan format jpeg, png, jpg',
            'foto.max' => 'Ukuran foto tidak boleh lebih dari 5MB',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        //create user
        $insert = User::insert([
            'uuid' =>  Str::uuid(),
            'name' => $request->input('nama'),
            'role' => 'user',
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'foto' => '',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        if (!$insert) {
            return response()->json(['status'=>'error','message'=>'gagal register'], 400);
        }
        return $mailController->createVerifyEmail($request,$verify);
    }
    public function updateProfile(Request $request){
        // $userAuth = $request->input('user_auth');
        $validator = Validator::make($request->only('email_new', 'nama_lengkap', 'jenis_kelamin', 'no_telpon', 'foto'),
            [
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
            ],
        );
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        //check email
        $user = User::select('email','foto')->whereRaw("BINARY email = ?",[$request->input('user_auth')['email']])->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Admin tidak ditemukan'], 400);
        }
        //check email on table user
        if(!is_null($request->input('email_new') || !empty($request->input('email_new'))) && User::select('role')->whereRaw("BINARY email = ?",[$request->input('email_new')])->first() && $request->input('email_new') != $request->input('user_auth')['email']){
            return response()->json(['status' => 'error', 'message' => 'Email sudah digunakan'], 400);
        }
        //process file foto
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            if(!($file->isValid() && in_array($file->extension(), ['jpeg', 'png', 'jpg']))){
                return response()->json(['status'=>'error','message'=>'Format Foto tidak valid. Gunakan format jpeg, png, jpg'], 400);
            }
            $destinationPath = storage_path('app/user/');
            $fileToDelete = $destinationPath . $user['foto'];
            if (file_exists($fileToDelete) && !is_dir($fileToDelete)) {
                unlink($fileToDelete);
            }
            Storage::disk('user')->delete('foto/'.$user['foto']);
            $fotoName = $file->hashName();
            $fileData = Crypt::encrypt(file_get_contents($file));
            Storage::disk('user')->put('foto/' . $fotoName, $fileData);
        }
        //update profile
        $updateProfile = User::whereRaw("BINARY email = ?",[$request->input('user_auth')['email']])->update([
            'email'=> (empty($request->input('email_new')) || is_null($request->input('email_new'))) ? $request->input('user_auth')['email'] : $request->input('email_new'),
            'name' => $request->input('nama_lengkap'),
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'no_telpon' => $request->input('no_telpon'),
            'foto' => $request->hasFile('foto') ? $fotoName : $user['foto'],
            'updated_at'=> Carbon::now()
        ]);
        if (!$updateProfile) {
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui Profile'], 500);
        }
        //update cookie
        $jwtController = new JWTController();
        $email = $request->has('email_new') ? $request->input('email_new') : $request->input('user_auth')['email'];
        $data = $jwtController->createJWTWebsite($email,new RefreshToken());
        if(is_null($data)){
            return response()->json(['status'=>'error','message'=>'create token error'],500);
        }else{
            if($data['status'] == 'error'){
                return response()->json(['status'=>'error','message'=>$data['message']],400);
            }else{
                $data1 = ['email'=>$email,'number'=>$data['number']];
                $encoded = base64_encode(json_encode($data1));
                return response()->json(['status'=>'success','message'=>'Profile Anda berhasil di perbarui'])
                ->cookie('token1',$encoded,time()+intval(env('JWT_ACCESS_TOKEN_EXPIRED')))
                ->cookie('token2',$data['data']['token'],time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')))
                ->cookie('token3',$data['data']['refresh'],time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED')));
            }
        }
    }
    //update from profile
    public function updatePassword(Request $request){
        $validator = Validator::make($request->only('password_old', 'password', 'password_confirm'), [
            'password_old' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\p{P}\p{S}])[\p{L}\p{N}\p{P}\p{S}]+$/u',
            ],
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
            'email.email'=>'Email yang anda masukkan invalid',
            'password.required'=>'Password wajib di isi',
            'password.min'=>'Password minimal 8 karakter',
            'password.max'=>'Password maksimal 25 karakter',
            'password.regex'=>'Password terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
            'password_confirm.required'=>'Password konfirmasi harus di isi',
            'password_confirm.min'=>'Password konfirmasi minimal 8 karakter',
            'password_confirm.max'=>'Password konfirmasi maksimal 25 karakter',
            'password_confirm.regex'=>'Password konfirmasi terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        $passOld = $request->input('password_old');
        $pass = $request->input('password');
        $passConfirm = $request->input('password_confirm');
        if($pass !== $passConfirm){
            return response()->json(['status'=>'error','message'=>'Password Harus Sama'],400);
        }
        //check email
        $user = User::select('password')->whereRaw("BINARY email = ?",[$request->input('user_auth')['email']])->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Admin tidak ditemukan'], 400);
        }
        if(!password_verify($passOld,$user->password)){
            return response()->json(['status'=>'error','message'=>'Password salah'],400);
        }
        //update password
        $updatePassword = User::whereRaw("BINARY email = ?",[$request->input('user_auth')['email']])->update([
            'password'=>Hash::make($pass),
            'updated_at'=> Carbon::now()
        ]);
        if (!$updatePassword) {
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui password admin'], 500);
        }
        return response()->json(['status' =>'success','message'=>'Password Admin berhasil di perbarui']);
    }
    public function logout(Request $request, JWTController $jwtController){
        $email = $request->input('email');
        $number = $request->input('number');
        if(empty($email) || is_null($email)){
            return response()->json(['status'=>'error','message'=>'email empty'],400);
        }else if(empty($number) || is_null($number)){
            return response()->json(['status'=>'error','message'=>'token empty'],400);
        }else{
            $deleted = $jwtController->deleteRefreshWebsite($email,$number);
            if($deleted['status'] == 'error'){
                return response()->json(['status'=>'error','message'=>'Logout gagal']);
            }else{
                return response()->json(['status'=>'success','message'=>'Logout berhasil'])->withCookie(Cookie::forget('token1'))->withCookie(Cookie::forget('token2'))->withCookie(Cookie::forget('token3'))->withCookie(Cookie::forget('cart'));
            }
        }
    }
}