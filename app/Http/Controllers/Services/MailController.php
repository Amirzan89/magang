<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Security\AESController;
use App\Models\User;
use App\Models\Verifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Jobs\SendResetPassword;
use App\Jobs\SendVerifyEmail;
use App\Jobs\SendFooterMail;
use App\Mail\FooterMail;
use App\Mail\ForgotPassword;
use App\Mail\VerifyEmail;
use Carbon\Carbon;
class MailController extends Controller
{
    private static $conditionOTP = [ 5, 15, 30, 60];
    public static function getConditionOTP(){
        return self::$conditionOTP;
    }
    public function sendMailFooter(Request $request){
        $validator = Validator::make($request->only('email'),
            [
                'email'=>'required|email',
            ],[
                'email.required' => 'Email wajib di isi',
                'email.email'=>'Email yang anda masukkan invalid',
            ],
        );
        if ($validator->fails()){
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages){
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        //send email
        // $data = [
        //     'email' => $request->input('email')
        // ];
        // dispatch(new SendFooterMail($data));
        // Mail::to($request->input('email'))->send(new FooterMail($data));
        $enc = app()->make(AESController::class)->encryptResponse(['message' => 'Email sudah dikirimkan'], $request->input('key'), $request->input('iv'));
        return response()->json(['status' => 'success', 'message' => $enc]);
    }
    public function createVerifyEmail(Request $request, Verifikasi $verify){
        $validator = Validator::make($request->all(), [
            'email'=>'required|email',
        ],[
            'email.required'=>'Email wajib di isi',
            'email.email'=>'Email yang anda masukkan invalid',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors = $errorMessages[0];
            }
            return ['status' => 'error', 'message' => $errors,'code' => 400];
        }
        $email = $request->input('email');
        //check email on table user
        $user = User::select('id_user', 'nama_lengkap')->whereRaw("BINARY email = ?",[$request->input('email')])->first();
        if ($user === null) {
            if($request->path() === 'verify/create/email' && $request->isMethod("get")){
                return ['status'=>'error','message'=>'email invalid'];
            }else{
                return ['status'=>'error','message'=>'email invalid','code'=>400];
            }
        }
        //check if user have create verify email
        $verifyDb = Verifikasi::select('send','updated_at')->whereRaw("BINARY email = ?",[$request->input('email')])->where('deskripsi', 'email')->first();
        if ($verifyDb === null) {
            //for register
            $verificationCode = mt_rand(100000, 999999);
            $linkPath = Str::random(50);
            $verificationLink = URL::to('/verify/email/'.$linkPath);
            $verify->email = $email;
            $verify->kode_otp = $verificationCode;
            $verify->link = $linkPath;
            $verify->deskripsi = 'email';
            $verify->send = 1;
            $verify->id_user = $user['id_user'];
            if(!$verify->save()){
                return ['status'=>'error','message'=>'fail create verify email','code'=>400];
            }
            $data = ['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>urldecode($verificationLink)];
            // dispatch(new SendVerifyEmail($data));
            Mail::to($email)->send(new VerifyEmail($data));
            return ['status'=>'success','message'=>'Akun Berhasil Dibuat Silahkan verifikasi email','code'=>200,'data'=>['waktu'=>Carbon::now()->addMinutes(self::$conditionOTP[0])]];
        }
        //checking if user have create verify email
        $expTime = self::$conditionOTP[($verifyDb['send'] - 1)];
        if (Carbon::parse($verifyDb->updated_at)->diffInMinutes(Carbon::now()) <= $expTime) {
            return response()->json(['status'=>'success','message'=>'Kami sudah mengirim email verifikasi ','data'=>['waktu' => Carbon::now()->addMinutes(self::$conditionOTP[min($verifyDb['send'], count(self::$conditionOTP)) - 1])]]);
        }
        //if after desired time then update code
        $verificationCode = mt_rand(100000, 999999);
        $linkPath = Str::random(50);
        $verificationLink = URL::to('/verify/email/'.$linkPath);
        if(is_null(DB::table('verifikasi')->whereRaw("BINARY email = ? AND deskripsi = 'email'",[$email])->update(['kode_otp'=>$verificationCode,'link'=>$linkPath, 'send'=> min($verifyDb['send'] + 1, count(self::$conditionOTP))]))){
            return response()->json(['status'=>'error','message'=>'fail create verify email'], 400);
        }
        $data = ['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>urldecode($verificationLink)];
        //resend email
        // dispatch(new SendVerifyEmail(['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink]));
        Mail::to($email)->send(new VerifyEmail(['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink]));
        return response()->json(['status'=>'success','message'=>'email benar kami kirim ulang kode ke anda silahkan cek email','data'=>['waktu'=>Carbon::now()->addMinutes(self::$conditionOTP[min($verifyDb['send'] + 1, count(self::$conditionOTP)) - 1])]]);
    }
    //send email forgot password for admin and mobile
    public function createForgotPassword(Request $request, Verifikasi $verify){
        $validator = Validator::make($request->all(), [
            'email'=>'required|email',
        ],[
            'email.required'=>'Email wajib di isi',
            'email.email'=>'Email yang anda masukkan invalid',
        ]);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                $errors = $errorMessages[0];
            }
            return response()->json(['status' => 'error', 'message' => $errors], 400);
        }
        $email = $request->input('email');
        //check email on table user
        $user = User::select('id_user', 'nama_lengkap', 'role')->whereRaw("BINARY email = ?",[$request->input('email')])->first();
        if (is_null($user)) {
            return response()->json(['status'=>'error','message'=>'Email tidak terdaftar !'], 400);
        }
        //check role for reset password
        if((Str::startsWith('/'.$request->path(), '/api') && strpos($user['role'], 'admin')) || (!Str::startsWith('/'.$request->path(), '/api') && $user['role'] === 'user')){
            return response()->json(['status'=>'error','message'=>'User Unauthorized'], 403);
        }
        //checking process if user have create verify email or not
        $verifyDb = Verifikasi::select('send','updated_at')->whereRaw("BINARY email = ?",[$request->input('email')])->where('deskripsi', 'password')->first();
        if (is_null($verifyDb)) {
            //if user haven't create email forgot password
            $verificationCode = mt_rand(100000, 999999);
            $linkPath = Str::random(50);
            $verificationLink = URL::to('/verify/password/' . $linkPath);
            $verify->email = $email;
            $verify->kode_otp = $verificationCode;
            $verify->link = $linkPath;
            $verify->deskripsi = 'password';
            $verify->send = 1;
            $verify->id_user = $user['id_user'];
            if($verify->save()){
                $data = ['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink];
                // dispatch(new SendResetPassword($data));
                Mail::to($email)->send(new ForgotPassword($data));
                return response()->json(['status'=>'success','message'=>'kami akan kirim kode ke anda silahkan cek email','data'=>['waktu'=>Carbon::now()->addMinutes(self::$conditionOTP[0])]]);
            }else{
                return response()->json(['status'=>'error','message'=>'fail create forgot password'],500);
            }
        }
        //checking if user have create verify email
        $expTime = self::$conditionOTP[($verifyDb['send'] - 1)];
        if (Carbon::parse($verifyDb->updated_at)->diffInMinutes(Carbon::now()) <= $expTime){
            return response()->json(['status'=>'success','message'=>'Kami sudah mengirim Otp silahkan cek mail anda ','data'=>['waktu' => Carbon::now()->addMinutes(self::$conditionOTP[min($verifyDb['send'], count(self::$conditionOTP)) - 1])]]); 
        }
        //if after desired time then update code
        $verificationCode = mt_rand(100000, 999999);
        $linkPath = Str::random(50);
        $verificationLink = URL::to('/verify/password/' . $linkPath);
        if(is_null(DB::table('verifikasi')->whereRaw("BINARY email = ? AND deskripsi = 'password'",[$email])->update(['kode_otp'=>$verificationCode, 'link'=>$linkPath, 'updated_at' => Carbon::now(), 'send' => min($verifyDb['send'] + 1, count(self::$conditionOTP))]))){
            return response()->json(['status'=>'error','message'=>'fail create forgot password'], 500);
        }
        // dispatch(new SendResetPassword(['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink]));
        Mail::to($email)->send(new ForgotPassword(['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink]));
        return response()->json(['status'=>'success','message'=>'email benar kami kirim ulang kode ke anda silahkan cek email','data'=>['waktu'=>Carbon::now()->addMinutes(self::$conditionOTP[min($verifyDb['send'] + 1, count(self::$conditionOTP)) - 1])]]);
    }
    public function verifyEmail(Request $request){
        $email = $request->input('email');
        if(empty($email) || is_null($email)){
            return response()->json(['status'=>'error','message'=>'email empty'],404);
        }else{
            $prefix = "/verify/email/";
            if(($request->path() === $prefix) && $request->isMethod("post")){
                $linkPath = substr($request->path(), strlen($prefix));
                if(Verifikasi::select("link")->whereRaw("BINARY link = ?",[$linkPath])->limit(1)->exists()){
                    if(Verifikasi::select("email")->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
                        if(is_null(DB::table('users')->whereRaw("BINARY email = ?",[$email])->update(['email_verified'=>true]))){
                            return response()->json(['status'=>'error','message'=>'error verify email'],500);
                        }else{
                            return response()->json(['status'=>'success','message'=>'email verify success']);
                        }
                    }else{
                        return response()->json(['status'=>'error','message'=>'email invalid'],400);
                    }
                }else{
                    return response()->json(['status'=>'error','message'=>'link invalid'],400);
                }
            }else{
                return response()->json(['status'=>'error','message'=>'not found'],404);
            }
        }
    }
    // public function send(){
    //     Mail::to('amirzanfikri5@gmail.com')->send(new ForgotPassword(['data'=>'data']));
    //     return view('page.home');
    // }
}
?>