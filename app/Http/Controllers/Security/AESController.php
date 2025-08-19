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
        // $currentMonth = now()->format('m-Y');
        // $cachedMonth = Cache::get('aes_month');
        // $keyAES = Cache::get('aes_key');
        // if($cachedMonth !== $currentMonth || !$keyAES){
        //     $keyAES = bin2hex(random_bytes(32));
        //     Cache::put('aes_key', $keyAES);
        //     Cache::put('aes_month', $currentMonth);
        // }
        $currentMonth = now()->format('m-Y');
        $cachedMonth = Cache::get('aes_month');
        $keyAES = Cache::get('aes_key');
        if($cachedMonth !== $currentMonth || !$keyAES){
            $keyAESBinary = random_bytes(32);
            $keyAESHex = bin2hex($keyAESBinary);
            Token::whereMonth('created_at', '<', now()->month)->delete();
            Token::create([
                'aes_key' => $keyAESHex,
                'created_at' => now()
            ]);
            Cache::put('aes_key', $keyAESHex);
            Cache::put('aes_month', $currentMonth);
            $keyAES = $keyAESHex;
        }
        return $keyAES;
    }
    public function decryptRequest($cipher, $iv){
        $decrypt = openssl_decrypt(hex2bin($cipher), 'AES-256-CBC', hex2bin(Cache::get('aes_key')), OPENSSL_RAW_DATA, hex2bin($iv));
        if(!$decrypt){
            return ['status'=>'error','message'=>'Error Decrypt Payload','code'=>500];
        }
        return ['status'=>'success','data'=>json_decode($decrypt, true)];
    }
    public function encryptResponse($data, $iv){
        $encrypted = openssl_encrypt(json_encode($data), 'AES-256-CBC', hex2bin(Cache::get('aes_key')), OPENSSL_RAW_DATA, hex2bin($iv));
        return bin2hex($encrypted);
    }
    public function testEncrypt(Request $request){
        try{
            $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', $request->input('key'), OPENSSL_RAW_DATA, $request->input('iv'));
            return ['status' => 'success', 'data' => bin2hex($encrypted)];
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