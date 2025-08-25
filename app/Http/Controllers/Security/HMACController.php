<?php
namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use DateTime;
class HMACController extends Controller
{
    public function publicKeyHMAC(){
        $hmacKey = Cache::get("hmac_pub_key");
        if(is_null($hmacKey)){
            $hmacKey = hash_hmac('sha256', now(), Str::random(32), true);
            Cache::put("hmac_pub_key", base64_encode($hmacKey));
        }
        return $hmacKey;
    }
    public function genKeyHMAC($idUser){
        $hmacKey = Cache::get("hmac_key_user_{$idUser}");
        if(is_null($hmacKey)){
            $hmacKey = hash_hmac('sha256', $idUser . now(), Str::random(32), true);
            Cache::put("hmac_key_user_{$idUser}", base64_encode($hmacKey));
        }
        return $hmacKey;
    }
    public function delKeyHMAC($idUser){
        Cache::delete("hmac_key_user_{$idUser}");
    }
}