<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class TestingSimpleController extends Controller
{
    public function tesss(Request $request){
        $data = "Hello Worldepgegkepge!";
        // Generate kunci dan IV
        $key = random_bytes(32); // 256-bit
        $iv = random_bytes(16);  // 128-bit
        // Enkripsi
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $base64_encrypted = base64_encode($encrypted);
        // Dekripsi ulang untuk verifikasi
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        // Output
        echo "=== AES-256-CBC ENCRYPTION/DECRYPTION TEST === <br><br>";
        echo "Plaintext           : $data <br>";
        echo "Encrypted (base64)  : $base64_encrypted <br>";
        echo "Decrypted           : $decrypted <br>";
        echo "<br> === Keys (Base64) for Devglan Testing === <br>";
        echo "Key (base64)        : " . base64_encode($key) . "<br>";
        echo "Key       : " . $key . "<br>";
        echo "IV  (base64)        : " . base64_encode($iv) . "<br>";
        echo "IV        : " . $iv . "<br>";
    }
    public function testting(Request $request){
        return response()->json();
    }
}