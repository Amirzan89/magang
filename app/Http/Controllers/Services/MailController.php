<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\UtilityController;
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
    //send email forgot password for admin and mobile
    public function createForgotPassword(Request $request, UtilityController $utilityController, AESController $aesController){
        $validator = Validator::make($request->only('email'), [
            'email'=>'required|email',
        ],[
            'email.required'=>'Email wajib di isi',
            'email.email'=>'Email yang anda masukkan invalid',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json', 422);
        }
        $email = $request->input('email');
        $user = User::select('id_user', 'nama_lengkap')->whereRaw("BINARY email = ?",[$email])->first();
        if(is_null($user)){
            return $utilityController->getView($request, $aesController, '', ['message'=>'Email tidak terdaftar !'], 'json', 400);
        }
        $verifyDb = Verifikasi::select('send','updated_at')->whereRaw("BINARY email = ?",[$email])->where('deskripsi', 'password')->first();
        $verificationCode = mt_rand(100000, 999999);
        $token = bin2hex(random_bytes(16));
        $tokenHash = hash_hmac('sha256', $token, config('app.key'));
        $verificationLink = URL::temporarySignedRoute('verify.password', now()->addMinutes(15), [
            'email' => $email,
            'token' => $token
        ]);
        if(is_null($verifyDb)){
            //if user haven't create email forgot password
            $verify = new Verifikasi();
            $verify->email = $email;
            $verify->kode_otp = $verificationCode;
            $verify->link = $tokenHash;
            $verify->deskripsi = 'password';
            $verify->send = 1;
            $verify->id_user = $user['id_user'];
            if(!$verify->save()){
                return $utilityController->getView($request, $aesController, '', ['message'=>'fail create forgot password'], 'json', 500);
            }
            $data = ['name'=>$user->nama_lengkap,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink];
            dispatch(new SendResetPassword($data));
            return $utilityController->getView($request, $aesController, '', ['message'=>'kami akan kirim kode ke anda silahkan cek email','data'=>['waktu'=>Carbon::now()->addMinutes(self::$conditionOTP[0])]], 'json');
        }
        $expTime = self::$conditionOTP[($verifyDb['send'] - 1)];
        if(Carbon::parse($verifyDb->updated_at)->diffInMinutes(Carbon::now()) <= $expTime){
            return $utilityController->getView($request, $aesController, '', [
                'message' => 'Kami sudah mengirim kode OTP, silahkan cek email anda',
                'data' => ['waktu' => Carbon::now()->addMinutes(self::$conditionOTP[min($verifyDb['send'], count(self::$conditionOTP)) - 1])]
            ], 'json');
        }
        Verifikasi::whereRaw("BINARY email = ? AND deskripsi = 'password'", [$email])->update([
            'kode_otp' => $verificationCode,
            'link' => $tokenHash,
            'updated_at' => Carbon::now(),
            'send' => min($verifyDb['send'] + 1, count(self::$conditionOTP))
        ]);
        $data = ['name' => $user->nama_lengkap,'email' => $email,'code' => $verificationCode,'link' => $verificationLink];
        dispatch(new SendResetPassword($data));
        return $utilityController->getView($request, $aesController, '', [
            'message' => 'Kami kirim ulang kode OTP, silahkan cek email anda',
            'data' => ['waktu' => Carbon::now()->addMinutes(self::$conditionOTP[min($verifyDb['send'] + 1, count(self::$conditionOTP)) - 1])]
        ], 'json');
    }
    // public function send(){
    //     Mail::to('amirzanfikri5@gmail.com')->send(new ForgotPassword(['data'=>'data']));
    //     return view('page.home');
    // }
}
?>