<?php
namespace App\Http\Controllers\Services\Encryption;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Error;
class TestingSimpleController extends Controller
{
    public function tesss(Request $request){
        $data = "Hello Worldepgegkepge!";
        // Generate kunci dan IV
        $key = random_bytes(32);  // 256-bit
        $iv = random_bytes(16);  // 128-bit
        $key = openssl_random_pseudo_bytes(32); // 256-bit
        $iv = openssl_random_pseudo_bytes(16);  // 128-bit
        // Enkripsi
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $base64_encrypted = base64_encode($encrypted);
        // Dekripsi ulang untuk verifikasi
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        // Output
        echo "=== AES-256-CBC ENCRYPTION/DECRYPTION TEST === <br><br>";
        echo "Plaintext           : $data <br>";
        echo "Encrypted (Hex)  : " . bin2hex($encrypted) . "<br>";
        echo "Encrypted (base64)  : $base64_encrypted <br>";
        echo "Decrypted           : $decrypted <br>";
        echo "<br> === Keys (Base64) for Devglan Testing === <br>";
        echo "Key (Hex)        : " . bin2hex($key) . "<br>";
        echo "Key (base64)        : " . base64_encode($key) . "<br>";
        echo "Key       : " . $key . "<br>";
        echo "IV  (Hex)        : " . bin2hex($iv) . "<br>";
        echo "IV  (base64)        : " . base64_encode($iv) . "<br>";
        echo "IV        : " . $iv . "<br>";
    }
    public function testEncrypt(Request $request){
        try{
            $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', hex2bin($request->input('key')), OPENSSL_RAW_DATA, hex2bin($request->input('iv')));
            return response()->json(['status'=>'success','data'=>bin2hex($encrypted)]);
        }catch(Error $e){
            return response()->json($e, 500);
        }
    }
    public function testDecrypt(Request $request){
        try{
            $decrypted = openssl_decrypt(hex2bin($request->input('chiper')), 'AES-256-CBC', hex2bin($request->input('key')), OPENSSL_RAW_DATA, hex2bin($request->input('iv')));
            return response()->json(['status'=>'success','data'=>$decrypted]);
        }catch(Error $e){
            return response()->json($e, 500);
        }
    }
    private function RSA_ping(Request $request){
        $spkiB64 = $request->input('clientPublicSpkiB64');
        $clientNonceB64 = $request->input('clientNonce');
        abort_unless($spkiB64 && $clientNonceB64, 400, 'bad req');
        $pub = PublicKeyLoader::load(base64_decode($spkiB64))
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');
        $aes  = random_bytes(32);
        $hmac = random_bytes(32);
        $keyId = random_bytes(16);
        $serverNonce = random_bytes(16);
        $exp = (int)(microtime(true)*1000) + 30*60*1000; // 30 menit
        $expBuf = pack('J', $exp); // 8 byte big-endian (PHP 8.0+)
        $payload = $aes.$hmac.$keyId.base64_decode($clientNonceB64).$serverNonce.$expBuf;
        $encKey = $pub->encrypt($payload);
        // Simpan di cache (by keyId) untuk verifikasi berikutnya / rotasi
        $keyIdHex = bin2hex($keyId);
        Cache::put("sess:$keyIdHex", ['aes'=>$aes, 'hmac'=>$hmac, 'exp'=>$exp], now()->addMinutes(30));
        return response()->json([
            'encKey' => base64_encode($encKey),
            'serverNonce' => base64_encode($serverNonce),
            'exp' => $exp
        ]);
    }
    // public function query_rsa(Request $request){
    //     try{
    //         $keyPyxis = env('PYXIS_KEY');
    //         $ivPyxis = env('PYXIS_IV');
    //         $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', hex2bin($keyPyxis), OPENSSL_RAW_DATA, hex2bin($ivPyxis));
    //         $body = $request->all();
    //         $res = Http::post($request->input('url'), $request->except('url'))->json();
    //         $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', hex2bin($request->input('key')), OPENSSL_RAW_DATA, hex2bin($request->input('iv')));
    //         // return response()->json(['status'=>'success','data'=>bin2hex($encrypted)]);
    //         $decrypted = openssl_decrypt(hex2bin($request->input('chiper')), 'AES-256-CBC', hex2bin($request->input('key')), OPENSSL_RAW_DATA, hex2bin($request->input('iv')));
    //         return response()->json(['status'=>'success','data'=>$decrypted]);
    //     }catch(Error $e){
    //         return response()->json($e, 500);
    //     }
    // }
    public function query_ecdh(Request $request){
        try{
            $keyPyxis = env('PYXIS_KEY');
            $ivPyxis = env('PYXIS_IV');
            $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', hex2bin($keyPyxis), OPENSSL_RAW_DATA, hex2bin($ivPyxis));
            $body = $request->all();
            $res = Http::post($request->input('url'), $request->except('url'))->json();
            $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', hex2bin($request->input('key')), OPENSSL_RAW_DATA, hex2bin($request->input('iv')));
            // return response()->json(['status'=>'success','data'=>bin2hex($encrypted)]);
            $decrypted = openssl_decrypt(hex2bin($request->input('chiper')), 'AES-256-CBC', hex2bin($request->input('key')), OPENSSL_RAW_DATA, hex2bin($request->input('iv')));
            return response()->json(['status'=>'success','data'=>$decrypted]);
        }catch(Error $e){
            return response()->json($e, 500);
        }
    }
}