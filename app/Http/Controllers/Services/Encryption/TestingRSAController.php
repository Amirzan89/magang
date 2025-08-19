<?php
namespace App\Http\Controllers\Services\Encryption;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Laravel\Passport\Token;
use Carbon\Carbon;
use Error;
class TestingRSAController extends Controller
{
    private function publicKeyHMAC(){
        $hmacKey = Cache::get("hmac_pub_key");
        if(is_null($hmacKey)){
            $hmacKey = bin2hex(hash_hmac('sha256', now(), Str::random(32), true));
            Cache::put("hmac_pub_key", $hmacKey);
        }
        return $hmacKey;
    }
    private function genKeyHMAC($idUser){
        $hmacKey = Cache::get("hmac_key_user_{$idUser}");
        if(is_null($hmacKey)){
            $hmacKey = bin2hex(hash_hmac('sha256', $idUser . now(), Str::random(32), true));
            Cache::put("hmac_key_user_{$idUser}", $hmacKey);
        }
        return $hmacKey;
    }
    private function delKeyHMAC($idUser){
        Cache::delete("hmac_key_user_{$idUser}");
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
            // Token::whereMonth('created_at', '<', now()->month)->delete();
            // Token::create([
            //     'aes_key' => $keyAESHex,
            //     'created_at' => now()
            // ]);
            Cache::put('aes_key', $keyAESHex);
            Cache::put('aes_month', $currentMonth);
            $keyAES = $keyAESHex;
        }
        return $keyAES;
    }
    // public function handshake_sealed(Request $request){
    //     $aes  = random_bytes(32);
    //     $hmac = random_bytes(32);
    //     $exp  = now()->addMinutes(20)->getTimestamp(); // TTL 20 menit (atur sesukamu)
    //     $jti  = bin2hex(random_bytes(8)); // id token opsional
    //     // Payload untuk server saja (client TIDAK bisa buka ini)
    //     $sealedPayload = [
    //         'k'   => bin2hex($aes),
    //         'm'   => bin2hex($hmac),
    //         'exp' => $exp,
    //         'jti' => $jti,
    //         // 'kid' => 'v1', // optional: buat key rotation
    //     ];
    //     // Laravel Crypt: AEAD (conf + integrity) pakai APP_KEY
    //     $sealed = Crypt::encryptString(json_encode($sealedPayload));
    //     // Kirim sealed + raw key ke client (raw key akan dipakai client untuk enkripsi)
    //     // NB: raw key aman selama via HTTPS; simpan di sessionStorage/in-memory
    //     return response()->json([
    //         'sealed'     => $sealed,
    //         'aes_hex'    => bin2hex($aes),
    //         'hmac_hex'   => bin2hex($hmac),
    //         'exp'        => $exp,
    //         'token_type' => 'sealed-v1'
    //     ]);
    // }
    public function handsake_rsa_old(Request $request){
        $spkiB64 = $request->input('clientPublicSpkiB64');
        $clientNonceB64 = $request->input('clientNonce');
        abort_unless($spkiB64 && $clientNonceB64, 400, 'bad req');
        $pub = PublicKeyLoader::load(base64_decode($spkiB64))->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->withMGFHash('sha256');
        $aes  = random_bytes(32);
        $hmac = random_bytes(32);
        $keyId = random_bytes(16);
        $serverNonce = random_bytes(16);
        $exp = (int)(microtime(true)*1000) + 1000*60*30; // 30 menit
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
    public function handsake_rsa(Request $request){
        $aesKey  = $this->genKeyAes();
        $hmac = $this->publicKeyHMAC();
        $keyId = random_bytes(16);
        $serverNonce = random_bytes(16);
        $exp = (int)(microtime(true)*1000) + 1000*60*30;
        $expBuf = pack('J', $exp); // 8 byte big-endian (PHP 8.0+)
        $spkiB64 = $request->input('clientPublicSpkiB64');
        $clientNonceB64 = $request->input('clientNonce');
        abort_unless($spkiB64 && $clientNonceB64, 400, 'bad req');
        $pub = PublicKeyLoader::load(base64_decode($spkiB64))->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->withMGFHash('sha256');
        // $keyIdHex = bin2hex($keyId);
        // Cache::put("sess:$keyIdHex", ['aes'=>$aes, 'hmac'=>$hmac, 'exp'=>$exp], now()->addMinutes(30));
        $payload = hex2bin($aesKey).hex2bin($hmac).$keyId.base64_decode($clientNonceB64).$serverNonce.$expBuf;
        $encKey = $pub->encrypt($payload);
        $jti  = bin2hex(random_bytes(8));
        $sealedPayload = [
            'k'   => $aesKey,
            'm'   => $hmac,
            'exp' => $exp,
            'jti' => $jti,
            // 'kid' => 'v1', // optional: buat key rotation
        ];
        $sealed = Crypt::encryptString(json_encode($sealedPayload));
        return response()->json(['status' => 'success', 'data' => [
            'sealed' => $sealed,
            'encKey' => bin2hex($encKey),
            'hmac' => $hmac,
            'exp' => $exp,
            'token_type' => 'handsake-v1',
            'expires_in' => 7200
        ]]);
    }
    public function query_rsa(Request $request){
        try{
            $sealed = $request->header('X-Sealed') ?? $request->input('sealed');
            abort_unless($sealed, 401, 'missing sealed token');
            $sePayload = json_decode(Crypt::decryptString($sealed), true);
            // Cek expiry
            if(($sePayload['exp'] ?? 0) < time()){
                abort(401, 'sealed expired');
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
                abort(400, 'tampered');
            }
            $reqDec = openssl_decrypt($ct, 'AES-256-CBC', hex2bin($sePayload['k']), OPENSSL_RAW_DATA, $iv);
            $keyPyxis = env('PYXIS_KEY2');
            $ivPyxis = env('PYXIS_IV');
            $bodyData = base64_encode(openssl_encrypt(json_encode($reqDec), 'AES-256-CBC', "A9CCF340D9A490104AC5159B8E1CBXXX", OPENSSL_RAW_DATA, $ivPyxis));
            $bodyReq = [
                'apikey' => $keyPyxis,
                'uniqueid' => $ivPyxis,
                'timestamp' => Carbon::now()->toString(),
                'message' => $bodyData,
            ];
            echo 'coba ';
            var_dump($bodyReq);
            echo "<br>";
            $res =  Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withBody(json_encode($bodyReq), 'application/json')->post('http://sereg.alcorsys.com:8989/JQuery')->json();
            var_dump($res);
            echo "<br>";
            echo "<br>";
            var_dump(openssl_decrypt(hex2bin($res['message']), 'AES-256-CBC', "A9CCF340D9A490104AC5159B8E1CBXXX", OPENSSL_RAW_DATA, $ivPyxis));
            echo "<br>";
            exit();
            // $decrypted = openssl_decrypt($res['message'], 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis);
            // var_dump($decrypted);
            // echo "<br>";
            // return response()->json(['status'=>'success','data'=>$decrypted]);
        }catch(RequestException $e){
            return response()->json([
                'error' => 'An error occurred with the external service.',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], $e->response->status());
        // } catch(\Throwable $e){
        //     echo "rrrorr seallledd";
        //     abort(401, 'bad sealed');
        }catch(Error $e){
            return response()->json([
                'error' => 'An unexpected server error occurred.',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}