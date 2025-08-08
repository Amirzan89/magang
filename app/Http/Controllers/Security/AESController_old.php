<?php
namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Error;
class AESController_old extends Controller
{
    public function FirstTime(HMACController $hmacController){
        return response()->json(['status'=>'success', 'data'=>[
            'aes_key'=>$this->genKeyAes(),
            'aes_iv'=>bin2hex(random_bytes(16)),
            'hmac_key'=>$hmacController->publicKeyHMAC(),
            'expires_in'=>7200
        ]]);
    }
    public function genKeyAes(){
        $currentMonth = now()->format('m-Y');
        $cachedMonth = Cache::get('aes_key_month');
        $keyAES = Cache::get('aes_key');
        if ($cachedMonth !== $currentMonth || !$keyAES) {
            $keyAES = bin2hex(random_bytes(32));
            Cache::put('aes_key', $keyAES);
            Cache::put('aes_key_month', $currentMonth);
        }
        return $keyAES;
    }
    public function decryptRequest($cipher, $key, $ivClient){
        $lCipher = strlen($cipher);
        $cipherHex = hex2bin(substr($cipher, 0, $lCipher - 32));
        $iv = hex2bin(substr($cipher, $lCipher - 32, 32));
        $keyy = Cache::get('aes_key');
        echo $ivClient;
        echo "<br>";
        echo substr($cipher, $lCipher - 32, 32);
        echo "<br>";
        echo "<br>";
        echo "<br>";
        echo "<br>";
        echo $key;
        echo "<br>";
        echo $keyy;
        echo "<br>";
        // $decrypt = openssl_decrypt($cipherHex, 'AES-256-CBC', hex2bin(Cache::get('aes_key')), OPENSSL_RAW_DATA, $iv);
        $decrypt = openssl_decrypt($cipherHex, 'AES-256-CBC', hex2bin(Cache::get('aes_key')), OPENSSL_RAW_DATA, $iv);
        var_dump($decrypt);
        exit();
        if(!$decrypt){
            return ['status'=>'error','message'=>'Error Decrypt Payload','code'=>500];
        }
        return ['status'=>'success','data'=>json_decode($decrypt)];
    }
    public function encryptResponse($data){
        if(now()->format('m') !== Cache::get('aes_key_month')){
            $keyAES = random_bytes(32);
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