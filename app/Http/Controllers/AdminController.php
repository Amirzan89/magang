<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Auth;
use App\Models\Admin;
class AdminController extends Controller
{
    public function createAdmin(Request $rt){
        $validator = Validator::make($rt->only('email', 'nama_admin', 'role', 'password'), [
            'email'=>'required|email',
            'nama_admin' => 'required|min:3|max:50',
            'role' => 'required|in:super_admin,admin_chat,admin_pemesanan',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\p{P}\p{S}])[\p{L}\p{N}\p{P}\p{S}]+$/u',
            ],
        ],[
            'email.required'=>'Email wajib di isi',
            'email.email'=>'Email yang anda masukkan invalid',
            'nama_admin.required' => 'Nama admin wajib di isi',
            'nama_admin.min'=>'Nama admin minimal 3 karakter',
            'nama_admin.max' => 'Nama admin maksimal 50 karakter',
            'role.required' => 'Role admin harus di isi',
            'role.in' => 'Role admin tidak valid',
            'password.required'=>'Password wajib di isi',
            'password.min'=>'Password minimal 8 karakter',
            'password.max'=>'Password maksimal 25 karakter',
            'password.regex'=>'Password terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
        ]);
        if($validator->fails()){
            $errors = [];
            foreach($validator->errors()->toArray() as $field => $errorMessages){
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        if(Auth::select("email")->whereRaw("BINARY email = ?",[$rt->input('email')])->exists()){
            return response()->json(['status'=>'error','message'=>'Email sudah digunakan'],400);
        }
        $idAuth = Auth::insertGetId([
            'email' => $rt->input('email'),
            'password' => Hash::make($rt->input('password')),
            'role'=>$rt->input('role'),
        ]);
        $ins = Admin::insert([
            'uuid' =>  Str::uuid(),
            'nama_admin' => $rt->input('nama_admin'),
            'id_auth' => $idAuth,
        ]);
        if(!$ins){
            return response()->json(['status'=>'error','message'=>'Gagal menambahkan data Admin'], 500);
        }
        return response()->json(['status'=>'success','message'=>'Data Admin berhasil ditambahkan']);
    }
    public function updateAdmin(Request $rt){
        $validator = Validator::make($rt->only('uuid', 'email','nama_admin', 'role', 'password'), [
            'uuid'=>'required|email',
            'email'=>'nullable|email',
            'nama_admin' => 'required|min:3|max:50',
            'role' => 'required|in:super_admin,admin_chat,admin_pemesanan',
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:25',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\p{P}\p{S}])[\p{L}\p{N}\p{P}\p{S}]+$/u',
            ],
        ],[
            'uuid.required'=>'Admin ID wajib di isi',
            'email.email'=>'Email yang anda masukkan invalid',
            'nama_admin.required' => 'Nama admin wajib di isi',
            'nama_admin.min'=>'Nama admin minimal 3 karakter',
            'nama_admin.max' => 'Nama admin maksimal 50 karakter',
            'role.required' => 'Role admin harus di isi',
            'role.in' => 'Role admin tidak valid',
            'password.min'=>'Password minimal 8 karakter',
            'password.max'=>'Password maksimal 50 karakter',
            'password.regex'=>'Password terdiri dari 1 huruf besar, huruf kecil, angka dan karakter unik',
        ]);
        if($validator->fails()){
            $errors = [];
            foreach($validator->errors()->toArray() as $field => $errorMessages){
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        $admin = Admin::select('auth.id_auth', 'auth.password', 'auth.role', 'admin.foto')->where('uuid',$rt->input('uuid'))->join('auth', 'admin.id_auth', '=', 'auth.id_auth')->firstOrFail();
        if(!is_null($rt->input('email') || !empty($rt->input('email'))) && $rt->input('email') != $admin['email'] && Auth::whereRaw("BINARY email = ?",[$rt->input('email')])->exists()){
            return response()->json(['status' => 'error', 'message' => 'Email sudah digunakan'], 400);
        }
        if(!is_null($rt->input('role')) && !empty($rt->input('role')) && !in_array($rt->input('role'), ['super_admin', 'admin_chat', 'admin_pemesanan'])){
            return response()->json(['status' => 'error', 'message' => 'Invalid Role'], 400);
        }
        $uT = Auth::where('id_auth', $admin['id_auth'])->update([
            'email' => (empty($rt->input('email')) || is_null($rt->input('email'))) ? $admin['email'] : $rt->input('email'),
            'password' => (empty($rt->input('password')) || is_null($rt->input('password'))) ? $admin['password']: Hash::make($rt->input('password')),
            'role' => (empty($rt->input('role')) || is_null($rt->input('role'))) ? $admin['role'] : $rt->input('role'),
        ]);
        $uA = Admin::where('id_auth', $admin['id_auth'])->update([
            'nama_admin'=>$rt->input('nama_admin'),
        ]);
        if(!$uT || !$uA){
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui data Admin'], 500);
        }
        return response()->json(['status'=>'success','message'=>'Data Admin berhasil diperbarui']);
    }
    public function updateProfile(Request $rt){
        $validator = Validator::make($rt->only('email', 'nama_admin'),
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
        $profile = Admin::select('auth.id_auth', 'auth.password', 'admin.foto')->where('auth.id_auth',$rt->user()['id_auth'])->join('auth', 'admin.id_auth', '=', 'auth.id_auth')->firstOrFail();
        if(!is_null($rt->input('email') || !empty($rt->input('email'))) && $rt->input('email') != $rt->user()['email'] && Admin::whereRaw("BINARY email = ?",[$rt->input('email')])->exists()){
            return response()->json(['status' => 'error', 'message' => 'Email sudah digunakan'], 400);
        }
        $updatedAuthProfile = Auth::where('id_auth',$rt->user()['id_auth'])->update([
            'email'=>(is_null($rt->input('email')) || empty($rt->input('email'))) ? $rt->user()['email'] : $rt->input('email'),
        ]);
        $updateProfile = Admin::where('id_auth',$rt->user()['id_auth'])->update([
            'nama_admin'=>$rt->input('nama_admin'),
        ]);
        if(!$updatedAuthProfile || !$updateProfile){
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui data Admin'], 500);
        }
        $rt->session()->regenerate();
        return response()->json(['status'=>'success','message'=>'Profile Anda berhasil di perbarui']);
    }
    public function updatePassword(Request $rt){
        $validator = Validator::make($rt->only('password_old', 'password', 'password_confirm'), [
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
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        $passOld = $rt->input('password_old');
        $pass = $rt->input('password');
        $passConfirm = $rt->input('password_confirm');
        if($pass !== $passConfirm){
            return response()->json(['status'=>'error','message'=>'Password Harus Sama'],400);
        }
        $profile = Auth::select('password')->where('id_auth',$rt->user()['id_auth'])->firstOrFail();
        if(!password_verify($passOld,$profile->password)){
            return response()->json(['status'=>'error','message'=>'Password salah'],400);
        }
        $updatePassword = Auth::where('id_auth',$rt->user()['id_auth'])->update([
            'password' => Hash::make($pass),
        ]);
        if(!$updatePassword){
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui password admin'], 500);
        }
        return response()->json(['status' =>'success','message'=>'Password Admin berhasil di perbarui']);
    }
    public function deleteAdmin(Request $rt){
        $validator = Validator::make($rt->only('uuid'), [
            'uuid' => 'required',
        ], [
            'uuid.required' => 'Admin ID wajib di isi',
        ]);
        if ($validator->fails()){
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $errorMessages){
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 400);
        }
        if(!Admin::where('uuid',$rt->input('uuid'))->delete()){
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus data Admin'], 500);
        }
        return response()->json(['status' => 'success', 'message' => 'Data Admin berhasil dihapus']);
    }
    public function logout(Request $rt){
        $rt->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'success', 'message' => 'Logout berhasil silahkan login kembali']);
    }
}
?>