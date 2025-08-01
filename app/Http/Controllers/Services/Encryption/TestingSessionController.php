<?php
namespace App\Http\Controllers\Services\Encryption;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
class TestingSessionController extends Controller
{
    public function tes_ping(Request $request){
        $key = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(16);
        Session::put('aes_key', $key);
        Session::put('aes_iv', $iv);
        return response()->json(['status'=>'success', 'data'=> ['key'=>bin2hex(Session::get('aes_key')),'iv'=>bin2hex(Session::get('aes_iv'))]]);
    }
    public function tesss(Request $request){
        $key = Session::get('aes_key');
        $iv  = Session::get('aes_iv');
        $dataReq = base64_decode($request->input('ciphertext'));
        $hasilReq = openssl_decrypt($dataReq, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return response()->json(['status'=>'success', 'data'=> base64_encode(openssl_encrypt(json_encode(['hasil'=>$hasilReq, 'dataku'=>'random1212ness']), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv))]);
    }
}