<?php
namespace App\Http\Middleware;
use Illuminate\Support\Str;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\JWTController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Response;
use App\Models\User;
use Closure;
class Authorization
{
    private $roleAdmin = ['super admin'];
    public function handle(Request $request, Closure $next){
        $userAuth = $request->input('user_auth');
        $path = '/'.$request->path();
        $checkEmail = function() use ($userAuth, $request){
            $email = $userAuth['email'] ?? $request->input('email');
            $validator = Validator::make(['email'=>$email], [
                'email' => 'required|email',
            ], [
                'email.required' => 'Email wajib di isi',
                'email.email' => 'Email yang anda masukkan invalid',
            ]);
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                    $errors[$field] = $errorMessages[0];
                    break;
                }
                return ['status' => 'error', 'message' => implode(', ', $errors),'code'=>400];
            }
            $email = $request->input('email');
            $userDB = User::select('role')->whereRaw("BINARY email = ?", [$request->input('email')])->limit(1)->get();
            if ($userDB->isEmpty()) {
                return ['status'=>'error','message'=>'User not found','code'=>404];
            }
            return ['status'=>'success','data' =>json_decode($userDB, true)[0]['role']];
        };
        if(isset($userAuth) && !empty($userAuth)){
            $role = $userAuth['role'];
            if(empty($role)){
                $cEmail = $checkEmail();
                if($cEmail['status'] == 'error'){
                    $code = $cEmail['code'];
                    unset($cEmail['code']);
                    return response()->json($cEmail,$code);
                }
                $role = $cEmail['data'];
                unset($cEmail);
            }
        }else{
            $cEmail = $checkEmail();
            if($cEmail['status'] == 'error'){
                $code = $cEmail['code'];
                unset($cEmail['code']);
                return response()->json($cEmail,$code);
            }
            $role = $cEmail['data'];
            unset($cEmail);
        }
        //only super admin can access /admin
        if(in_array($role,['admin event','admin seniman','admin tempat','masyarakat']) && Str::startsWith($path, '/admin')){
            return response()->json(['status'=>'error','message'=>'User Unauthorized'],403);
        }
        //only super admin and admin event can access /event
        if(in_array($role,['admin seniman','admin tempat','masyarakat']) && Str::startsWith($path, '/event')){
            return response()->json(['status'=>'error','message'=>'User Unauthorized'],403);
        }
        //only super admin and admin seniman can access /seniman or /pentas
        if(in_array($role,['admin event','admin tempat','masyarakat']) && (Str::startsWith($path, '/seniman') || Str::startsWith($path, '/pentas'))){
            return response()->json(['status'=>'error','message'=>'User Unauthorized'],403);
        }
        //only super admin and admin tempat can access /tempat
        if(in_array($role,['admin event','admin seniman','masyarakat']) && Str::startsWith($path, '/tempat')){
            return response()->json(['status'=>'error','message'=>'User Unauthorized'],403);
        }
        //when admin access mobile
        if(in_array($role,$this->roleAdmin) && Str::startsWith($path, '/mobile')){
            return response()->json(['status'=>'error','message'=>'User Unauthorized'],403);
        }
        return $next($request);
    }
}