<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Security\AESController;
use App\Mail\EventBookingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
class ThirdPartyController extends Controller
{
    public function pyxisAPI($reqDec = [], $url){
        $keyPyxis = env('PYXIS_KEY1');
        $ivPyxis = env('PYXIS_IV');
        $bodyData = strtoupper(bin2hex(openssl_encrypt(json_encode($reqDec), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis)));
        $bodyReq = [
            'apikey' => env('PYXIS_KEY2'),
            'uniqueid' => $ivPyxis,
            'timestamp' => now()->format('YmdHis'),
            'message' => $bodyData,
        ];  
        $res =  Http::withHeaders(['Content-Type' => 'application/json'])->post(env('PYXIS_URL'). $url, $bodyReq)->body();
        $decServer = json_decode(openssl_decrypt(hex2bin(json_decode($res, true)['message']), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis), true);
        return $decServer['data'];
    }
}