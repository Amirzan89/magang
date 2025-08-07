<?php
namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Error;
class AESController extends Controller
{
    public function FirstTime(HMACController $hmacController){
        return response()->json(['status'=>'success', 'data'=>[
            'aes_key'=>$this->genKeyAes(),
            'hmac_key'=>$hmacController->publicKeyHMAC(),
            'expires_in'=>7200
        ]]);
    }
    public function genKeyAes(){
        $currentMonth = now()->format('m-Y');
        $keyAES = base64_decode(Cache::get('aes_key'));
        if ($keyAES !== $currentMonth) {
            Cache::put('aes_key_month', $currentMonth);
            $keyAES = openssl_random_pseudo_bytes(32);
            Cache::put('aes_key', base64_encode($keyAES));
        }
        return bin2hex($keyAES);
    }
    public function encryptRequest($data){
        if(now()->format('m') !== Cache::get('aes_key_month')){
            $keyAES = openssl_random_pseudo_bytes(32);
            Token::orderBy()->where('created_at', )->delete();
            Token::create(['aes_key' => $keyAES]);
            Cache::put('aes_key_month', $keyAES);
            $keyAES = Token::latest('created_at')->value('aes_key');
        }
        $cipher = substr($request->input('cipher'), 0, 16);
        $iv = substr($request->input('chiper'), 16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', Cache::get('aes_key_month'), OPENSSL_RAW_DATA, $request->input('iv'));
        return bin2hex($encrypted . $iv);
    }
    public function decryptRequest(Request $request){
        $cipher = substr($request->input('cipher'), 0, 16);
        $iv = substr($request->input('chiper'), 16);
        return openssl_decrypt(hex2bin($cipher), 'AES-256-CBC', Cache::get('aes_key_month'), OPENSSL_RAW_DATA, $iv);
    }
    public function testEncrypt(Request $request){
        try{
            $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', $request->input('key'), OPENSSL_RAW_DATA, $request->input('iv'));
            return ['status' => 'success', 'data' => $encrypted];
        }catch(Error $e){
            return ['status' => 'error', 'message' => $e];
        }
    }
    public function decrypt(Request $request){
        try{
            $decrypted = openssl_decrypt(hex2bin($request->input('chiper')), 'AES-256-CBC', $request->input('key'), OPENSSL_RAW_DATA, $request->input('iv'));
            return ['status' => 'success', 'data' => $decrypted];
        }catch(Error $e){
            return ['status' => 'error', 'message' => $e];
        }
    }
}