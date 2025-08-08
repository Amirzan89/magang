<?php
namespace App\Http\Controllers\Services\Encryption;
use App\Http\Controllers\Controller;
use Error;
use Illuminate\Http\Request;
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
}