<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Crypt;
class AESHelper
{
    public static function encrypt($plaintext, $key)
    {
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertext);
    }

    public static function decrypt($encodedCipher, $key)
    {
        $data = base64_decode($encodedCipher);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }
}