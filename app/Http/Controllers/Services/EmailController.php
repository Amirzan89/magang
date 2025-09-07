<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Website\NotificationPageController;
use App\Models\User;
use App\Models\RefreshToken;
use App\Models\Verify;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use App\Mail\FooterEmail;
use App\Mail\ForgotPassword;
use App\Mail\VerifyEmail;
use Carbon\Carbon;
class EmailController extends Controller
{
    public function sendEmailFooter(Request $request){
        $validator = Validator::make($request->only('email', 'nama_admin'),
            [
                'email'=>'nullable|email',
                'nama_admin' => 'required|max:50',
            ],[
                'email.email'=>'Email yang anda masukkan invalid',
                'nama_admin.required' => 'Nama admin wajib di isi',
                'nama_admin.max' => 'Nama admin maksimal 50 karakter',
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
        $data = [
            //
        ];
        Mail::to($request->input('email'))->send(new FooterEmail($data));
    }
    public function getVerifyEmail(Request $request){
        $email = $request->input('email');
        if(empty($email) || is_null($email)){
            return response()->json(['status'=>'error','message'=>'email empty'],404);
        }else{
            if(User::select("email")->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
                if(Verify::select("email")->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
                    $dataDb = Verify::select()->whereRaw("BINARY email = ?",[$email])->limit(1)->get();
                    $data = json_decode(json_encode($dataDb));
                    $code = $data['code'];
                    $linkPath = $data['link'];
                    $verificationLink = URL::to('/verify/' . $linkPath);
                    return response()->json(['status'=>'success','data'=>['code'=>$code,'link'=>$verificationLink]]);
                }else{
                    return response()->json(['status'=>'error','message'=>'email invalid'],404);
                }
            }else{
                return response()->json(['status'=>'error','message'=>'email invalid'],404);
            }
        }
    }
    public function createVerifyEmail(Request $request, Verify $verify){
        $email = $request->input('email');
        if(empty($email) || is_null($email)){
            return ['status'=>'error','message'=>'email empty'];
        }else{
            //checking if email exist in table user
            if(User::select("email")->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
                //checking if email exist in table verify
                if(Verify::select("email")->whereRaw("BINARY email = ? AND description = 'verifyEmail'",[$email])->limit(1)->exists()){
                    //checking if user have create verify email
                    $currentDateTime = Carbon::now();
                    if (DB::table('verify')->whereRaw("BINARY email = ? AND description = 'verifyEmail'", [$email])->where('updated_at', '>=', $currentDateTime->subMinutes(15))->exists()) {
                        //if after 15 minute then update code
                        $verificationCode = mt_rand(100000, 999999);
                        $linkPath = Str::random(50);
                        $verificationLink = URL::to('/verify/email/'.$linkPath);
                        if(is_null(DB::table('verify')->whereRaw("BINARY email = ? AND description = 'verifyEmail'",[$email])->update(['code'=>$verificationCode,'link'=>$linkPath]))){
                            return ['status'=>'error','message'=>'fail create verify email'];
                        }else{
                            $inName = User::select('nama')->whereRaw("BINARY email = ?",[$email])->limit(1)->get();
                            $Iname = json_decode(json_encode($inName));
                            $data = ['name'=>$Iname,'email'=>$email,'code'=>$verificationCode,'link'=>urldecode($verificationLink)];
                            //resend email
                            Mail::to($email)->send(new VerifyEmail($data));
                            return ['status'=>'success','message'=>'success send verify email','data'=>['waktu'=>Carbon::now()->addMinutes(15)]];
                        }
                    }else{
                        return ['status'=>'error','message'=>'Kami sudah mengirim email verifikasi ','data'=>true];
                    }
                }else{
                    $verificationCode = mt_rand(100000, 999999);
                    $linkPath = Str::random(50);
                    $verificationLink = URL::to('/verify/email/'.$linkPath);
                    $verify->email = $email;
                    $verify->code = $verificationCode;
                    $verify->link = $linkPath;
                    $verify->description = 'verifyEmail';
                    if($verify->save()){
                        $inName = User::select('nama')->whereRaw("BINARY email = ?",[$email])->limit(1)->get();
                        $Iname = json_decode(json_encode($inName));
                        $data = ['name'=>$Iname,'email'=>$email,'code'=>$verificationCode,'link'=>urldecode($verificationLink)];
                        Mail::to($email)->send(new VerifyEmail($data));
                        return ['status'=>'Success','message'=>'Akun Berhasil Dibuat Silahkan verifikasi email','code'=>200,'data'=>['waktu'=>Carbon::now()->addMinutes(15)]];
                    }else{
                        return ['status'=>'error','message'=>'fail create verify email','code'=>400];
                    }
                }
            }else{
                if($request->path() === 'verify/create/email' && $request->isMethod("get")){
                    return ['status'=>'error','message'=>'email invalid'];
                }else{
                    return ['status'=>'error','message'=>'email invalid','code'=>400];
                }
            }
        }
    }
    //send email forgot password
    public function createForgotPassword(Request $request, Verify $verify){
        $email = $request->input('email');
        if(empty($email) || is_null($email)){
            return response()->json(['status'=>'error','message'=>'email empty'],400);
        }else{
            //checking if email exist in table user
            if(User::select("email")->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
                //checking if email exist in table verify
                if(Verify::select("email")->whereRaw("BINARY email = ? AND description = 'changePass'",[$email])->limit(1)->exists()){
                    //checking time
                    $currentDateTime = Carbon::now();
                    if (DB::table('verify')->whereRaw("BINARY email = ? AND description = 'changePass'",[$email])->where('updated_at', '<=', $currentDateTime->subMinutes(15))->exists()) {
                        //if after 15 minute then update code
                        $verificationCode = mt_rand(100000, 999999);
                        $linkPath = Str::random(50);
                        $verificationLink = URL::to('/verify/password/' . $linkPath);
                        if(is_null(DB::table('verify')->whereRaw("BINARY email = ? AND description = 'changePass'",[$email])->update(['code'=>$verificationCode,'link'=>$linkPath, 'updated_at' => Carbon::now()]))){
                            return response()->json(['status'=>'error','message'=>'fail create forgot password'],500);
                        }else{
                            $inName = User::select('nama')->whereRaw("BINARY email = ?",[$email])->limit(1)->get();
                            $Iname = json_decode(json_encode($inName));
                            $data = ['name'=>$Iname,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink];
                            Mail::to($email)->send(new ForgotPassword($data));
                            return response()->json(['status'=>'success','message'=>'email benar kami kirim kode ke anda silahkan cek email','data'=>['waktu'=>Carbon::now()->addMinutes(15)]]);
                        }
                    }else{
                        return response()->json(['status'=>'error','message'=>'Kami sudah mengirimkan otp lupa password silahkan cek mail anda','data'=>true],400);
                    }
                //if user haven't create email forgot password
                }else{
                    $verificationCode = mt_rand(100000, 999999);
                    $linkPath = Str::random(50);
                    $verificationLink = URL::to('/verify/password/' . $linkPath);
                    $verify->email = $email;
                    $verify->code = $verificationCode;
                    $verify->link = $linkPath;
                    $verify->description = 'changePass';
                    if($verify->save()){
                        $inName = User::select('nama')->whereRaw("BINARY email = ?",[$email])->limit(1)->get();
                        $Iname = json_decode(json_encode($inName));
                        // return response()->json('link '.$verificationLink);
                        $data = ['name'=>$Iname,'email'=>$email,'code'=>$verificationCode,'link'=>$verificationLink];
                        Mail::to($email)->send(new ForgotPassword($data));
                        return response()->json(['status'=>'success','message'=>'kami akan kirim kode ke anda silahkan cek email','data'=>['waktu'=>Carbon::now()->addMinutes(15)]]);
                    }else{
                        return response()->json(['status'=>'error','message'=>'fail create forgot password'],500);
                    }
                }
            }else{
                return response()->json(['status'=>'error','message'=>'Email tidak terdaftar !'],400);
            }
        }
    }
    public function verifyEmail(Request $request){
        $errorPage = new NotificationPageController();
        $email = $request->input('email');
        if(empty($email) || is_null($email)){
            return response()->json(['status'=>'error','message'=>'email empty'],404);
        }else{
            $prefix = "/verify/email/";
            if(($request->path() === $prefix) && $request->isMethod("post")){
                $linkPath = substr($request->path(), strlen($prefix));
                if(Verify::select("link")->whereRaw("BINARY link = ?",[$linkPath])->limit(1)->exists()){
                    if(Verify::select("email")->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
                        if(is_null(DB::table('users')->whereRaw("BINARY email = ?",[$email])->update(['email_verified'=>true]))){
                            return response()->json(['status'=>'error','message'=>'error verify email'],500);
                        }else{
                            // return redirect('/login');
                            return response()->json(['status'=>'success','message'=>'email verify success']);
                        }
                    }else{
                        return response()->json(['status'=>'error','message'=>'email invalid'],400);
                    }
                }else{
                    return response()->json(['status'=>'error','message'=>'link invalid'],400);
                }
            }else{
                // return view('page.error');
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