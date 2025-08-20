<?php
namespace App\Http\Controllers\Services\Encryption;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use InvalidArgumentException;
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
    private function normalizeKeyOrIv(string $input, int $expectedLength): string {
        if(ctype_xdigit($input) && strlen($input) % 2 === 0){
            $bin = hex2bin($input);
            if(strlen($bin) === $expectedLength){
                return $bin; // ✅ valid hex
            }
        }
        if(preg_match('/^[A-Za-z0-9+\/=]+$/', $input)){
            $bin = base64_decode($input, true);
            if($bin !== false && strlen($bin) === $expectedLength){
                return $bin; // ✅ valid base64
            }
        }
        if(strlen($input) === $expectedLength){
            return $input; // ✅ plain ASCII
        }
        throw new InvalidArgumentException("Input format tidak cocok untuk expected length $expectedLength");
    }
    public function testEncrypt(Request $request){
        try{
            $encrypted = openssl_encrypt($request->input('input'), 'AES-256-CBC', $this->normalizeKeyOrIv($request->input('key'), 32), OPENSSL_RAW_DATA, $this->normalizeKeyOrIv($request->input('iv'), 16));
            return response()->json(['status'=>'success','data'=>bin2hex($encrypted)]);
        }catch(Error $e){
            return response()->json($e, 500);
        }
    }
    public function testDecrypt(Request $request){
        try{
            $decrypted = openssl_decrypt(hex2bin($request->input('chiper')), 'AES-256-CBC', $this->normalizeKeyOrIv($request->input('key'), 32), OPENSSL_RAW_DATA, $this->normalizeKeyOrIv($request->input('iv'), 16));
            return response()->json(['status'=>'success','data'=>$decrypted]);
        }catch(Error $e){
            return response()->json($e, 500);
        }
    }
}