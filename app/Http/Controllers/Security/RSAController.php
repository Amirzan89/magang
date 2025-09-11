<?php
namespace App\Http\Controllers\Security;
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
class RSAController extends Controller
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
    public function handshake_rsa(Request $request){
        $aesKey  = $this->genKeyAes();
        $hmac = $this->publicKeyHMAC();
        $keyId = random_bytes(16);
        $serverNonce = random_bytes(16);
        $exp = (int)(microtime(true)*1000) + 1000*60*30;
        $expBuf = pack('J', $exp); // 8 byte big-endian (PHP 8.0+)
        $spkiB64 = $request->input('clientPublicSpkiB64');
        $clientNonceB64 = $request->input('clientNonce');
        if(!$spkiB64 && !$clientNonceB64){
            return response()->json(['status' => 'error', 'message' => 'bad req'], 400);
        }
        $pub = PublicKeyLoader::load(base64_decode($spkiB64))->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->withMGFHash('sha256');
        // $keyIdHex = bin2hex($keyId);
        // Cache::put("sess:$keyIdHex", ['aes'=>hex2bin($aesKey), 'hmac'=>$hmac, 'exp'=>$exp], now()->addMinutes(30));
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
        $merseal = Crypt::encryptString(json_encode($sealedPayload));
        return response()->json(['status' => 'success', 'data' => [
            'merseal' => $merseal,
            'encKey' => bin2hex($encKey),
            'hmac' => $hmac,
            'exp' => $exp,
            'token_type' => 'handshake-v1',
            'expires_in' => 7200
        ]]);
    }
    public function query_rsa(Request $request){
        try{
            $merseal = $request->header('X-Merseal') ?? $request->input('merseal');
            if(!$merseal){
                return response()->json(['status' => 'error', 'message' => 'missing merseal token'], 401);
            }
            $sePayload = json_decode(Crypt::decryptString($merseal), true);
            if(($sePayload['exp'] ?? 0) < time()){
                abort(401, 'merseal expired');
            }
            $hmac = hex2bin($sePayload['m']);
            $ivHex = $request->input('uniqueid');
            $ctHex = $request->input('chiper');
            $macHex= $request->input('mac');
            if(!$ivHex && !$ctHex && !$macHex){
                return response()->json(['status' => 'error', 'message' => 'bad payload'], 400);
            }
            $iv = hex2bin($ivHex);
            $ct = hex2bin($ctHex);
            $mac= hex2bin($macHex);
            if(!hash_equals(hash_hmac('sha256', $iv . $ct, $hmac, true), $mac)){
                abort(400, 'tampered');
            }
            $reqDec = openssl_decrypt($ct, 'AES-256-CBC', hex2bin($sePayload['k']), OPENSSL_RAW_DATA, $iv);
            $keyPyxis = env('PYXIS_KEY1');
            $ivPyxis = env('PYXIS_IV');
            $bodyData = strtoupper(bin2hex(openssl_encrypt($reqDec, 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis)));
            $bodyReq = [
                'apikey' => env('PYXIS_KEY2'),
                'uniqueid' => $ivPyxis,
                'timestamp' => now()->format('YmdHis'),
                'message' => $bodyData,
            ];
            $res =  Http::withHeaders(['Content-Type' => 'application/json'])->post('http://sereg.alcorsys.com:8989/JQuery', $bodyReq)->body();
            $decServer = json_decode(openssl_decrypt(hex2bin(json_decode($res, true)['message']), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis), true);
            return response()->json(['status'=>'success','data'=>bin2hex(openssl_encrypt(json_encode($decServer['data']), 'AES-256-CBC', hex2bin($sePayload['k']), OPENSSL_RAW_DATA, $iv))]);
        }catch(RequestException $e){
            return response()->json([
                'error' => 'An error occurred with the external service.',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], $e->response->status());
        } catch(\Throwable $e){
            abort(401, 'bad merseal');
        }catch(Error $e){
            return response()->json([
                'error' => 'An unexpected server error occurred.',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}