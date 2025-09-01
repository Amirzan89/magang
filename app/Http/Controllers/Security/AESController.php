<?php
namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Error;
class AESController extends Controller
{
    public function decryptRequest($cipher, $key, $iv){
        $decrypt = openssl_decrypt(hex2bin($cipher), 'AES-256-CBC', hex2bin($key), OPENSSL_RAW_DATA, hex2bin($iv));
        if(!$decrypt){
            return ['status'=>'error','message'=>'Error Decrypt Payload','statusCode'=>500];
        }
        return ['status'=>'success','data'=>json_decode($decrypt, true)];
    }
    public function encryptResponse($data, $key, $iv){
        return bin2hex(openssl_encrypt(json_encode($data), 'AES-256-CBC', hex2bin($key), OPENSSL_RAW_DATA, hex2bin($iv)));
    }
    public function mersealToken(Request $request){
        $merseal = $request->header('X-Merseal') ?? $request->input('merseal');
        if(!$merseal){
            return ['status'=>'error','message'=>'missing merseal token','statusCode'=>401];
        }
        $sePayload = json_decode(Crypt::decryptString($merseal), true);
        if(($sePayload['exp'] ?? 0) < time()){
            return ['status'=>'error','message'=>'merseal expired','statusCode'=>401];
        }
        $hmac = hex2bin($sePayload['m']);
        $ivHex = $request->input('uniqueid');
        $ctHex = $request->input('chiper');
        $macHex= $request->input('mac');
        abort_unless($ivHex && $ctHex && $macHex, 400, 'bad payload');
        $iv = hex2bin($ivHex);
        $ct = hex2bin($ctHex);
        $mac= hex2bin($macHex);
        if(!hash_equals(hash_hmac('sha256', $iv . $ct, $hmac, true), $mac)){
            return ['status'=>'error','message'=>'tampered','statusCode'=>400];
        }
        return ['status'=>'success','data'=>['key' => $sePayload['k'], 'iv' => $ivHex]];
    }
}