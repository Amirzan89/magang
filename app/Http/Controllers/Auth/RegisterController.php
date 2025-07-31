<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\MailController;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Models\Verify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class RegisterController extends Controller
{
    public function Register(Request $request, UserController $userController,MailController $mailController, Verify $verify){
        $validator = Validator::make($request->all(), [
            'email'=>'required | email',
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
            'nama'=>'required',
        ],[
            'nama.required'=>'nama wajib di isi',
            'email.required'=>'Email wajib di isi',
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
                $errors = $errorMessages[0];
            }
            return response()->json(['status' => 'error', 'message' => $errors], 400);
        }
        $email = $request->input("email");
        $pass = $request->input("password");
        $pass1 = $request->input("password_confirm");
        if (User::select("email")->whereRaw("BINARY email = ?",[$email])->limit(1)->exists()){
            return response()->json(['status'=>'error','message'=>'Email sudah digunakan'],400);
        }else if($pass !== $pass1){
            return response()->json(['status'=>'error','message'=>'Password Harus Sama'],400);
        }else{
            return $userController->createUser($request, $mailController, $verify);
        }
    }
}
?>