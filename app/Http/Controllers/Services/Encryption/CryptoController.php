<?php
namespace App\Http\Controllers\Services\Encryption;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class CryptoController extends Controller
{
    // --- Handshake: terima client SPKI (P-256), kirim balik server SPKI + simpan AES/HMAC di session ---
    public function handshake(Request $request){
        $clientSpkiB64 = $request->input('clientPubSpkiB64');
        $saltB64       = $request->input('saltB64');
        abort_unless($clientSpkiB64 && $saltB64, 400, 'bad req');
        $salt = base64_decode($saltB64, true);

        // 1) Import client public (SPKI) → OpenSSL key
        $clientPub = openssl_pkey_get_public($this->pemFromSpki(base64_decode($clientSpkiB64)));
        abort_unless($clientPub, 400, 'bad client pub');

        // 2) Generate server EC (P-256)
        $serverKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
        ]);
        abort_unless($serverKey, 500, 'ec gen failed');
        // 3) Derive shared secret (ECDH)
        $shared = $this->ecdh_derive($serverKey, $clientPub, 32); // 32 bytes

        // 4) HKDF-SHA256 -> 64B -> split AES/HMAC
        $okm = hash_hkdf('sha256', $shared, 64, 'v1', $salt, true);
        $aes  = substr($okm, 0, 32);
        $hmac = substr($okm, 32, 32);

        // 5) Export server public (SPKI base64) untuk dikirim ke client
        $serverDetails = openssl_pkey_get_details($serverKey);
        $serverSpkiDer = $this->spkiFromPem($serverDetails['key']); // DER
        $serverSpkiB64 = base64_encode($serverSpkiDer);
        // 6) Simpan kunci ke session (stateful & simpel)
        $sid = $request->session()->getId();
        session()->put('crypto.aes',  $aes);
        session()->put('crypto.hmac', $hmac);
        session()->put('crypto.exp',  time() + 30*60); // 30 menit
        return response()->json([
            'serverPubSpkiB64' => $serverSpkiB64,
            'saltB64' => $saltB64,
            'exp' => session('crypto.exp'),
        ]);
    }
    // --- Endpoint terenkripsi: verif HMAC(iv||cipher) -> decrypt AES-256-CBC ---
    public function secure(Request $request)
    {
        $aes  = session('crypto.aes');
        $hmac = session('crypto.hmac');
        $exp  = session('crypto.exp');
        abort_unless($aes && $hmac && $exp && $exp >= time(), 401, 'no session key');

        $ivHex = $request->input('iv');
        $ctHex = $request->input('data');
        $macHex= $request->input('mac');
        abort_unless($ivHex && $ctHex && $macHex, 400, 'bad payload');

        $iv = hex2bin($ivHex);
        $ct = hex2bin($ctHex);
        $mac= hex2bin($macHex);

        // 1) verify MAC first (Encrypt-then-MAC)
        $calc = hash_hmac('sha256', $iv.$ct, $hmac, true);
        if (!hash_equals($calc, $mac)) abort(400, 'tampered');

        // 2) decrypt AES-256-CBC
        $plain = openssl_decrypt($ct, 'AES-256-CBC', $aes, OPENSSL_RAW_DATA, $iv);
        abort_unless($plain !== false, 400, 'decrypt failed');

        // ... proses bisnis ...
        $requestesp = json_encode(['ok'=>true, 'echo'=>json_decode($plain, true)]);

        // optional: encrypt response simetris
        $iv2 = random_bytes(16);
        $ct2 = openssl_encrypt($requestesp, 'AES-256-CBC', $aes, OPENSSL_RAW_DATA, $iv2);
        $mac2= hash_hmac('sha256', $iv2.$ct2, $hmac, true);

        return response()->json([
            'iv'   => bin2hex($iv2),
            'data' => bin2hex($ct2),
            'mac'  => bin2hex($mac2),
        ]);
    }

    // === Helpers ===
    // Convert DER SPKI -> PEM PUBLIC KEY (untuk openssl_pkey_get_public)
    private function pemFromSpki(string $spkiDer): string {
        $b64 = chunk_split(base64_encode($spkiDer), 64, "\n");
        return "-----BEGIN PUBLIC KEY-----\n{$b64}-----END PUBLIC KEY-----\n";
    }
    // Extract DER SPKI dari PEM key detail
    private function spkiFromPem(string $pem): string {
        // openssl_pkey_get_details(['key' => $pem]) sudah kasih SPKI dalam $pem; parse ulang:
        // ambil konten base64 antara header/footer:
        if (preg_match('/-----BEGIN PUBLIC KEY-----(.+)-----END PUBLIC KEY-----/s', $pem, $m)) {
            return base64_decode(str_replace(["\r","\n"], '', trim($m[1])));
        }
        // kalau PEM yang diberikan adalah PRIVATE, ambil public dari details:
        $det = openssl_pkey_get_details(openssl_pkey_get_private($pem));
        return $det['key'];
    }

    // ECDH derive shared secret (serverPriv x clientPub)
    private function ecdh_derive($serverKey, $clientPub, int $len): string {
        // PHP menyediakan openssl_pkey_derive (OpenSSL 1.1+). 
        // Panjang shared P-256 = 32 byte.
        if (!function_exists('openssl_pkey_derive')) {
            abort(500, 'openssl_pkey_derive not available (need PHP with OpenSSL 1.1+)');
        }
        $shared = '';
        $ok = openssl_pkey_derive($clientPub, $serverKey, $shared);
        if (!$ok || strlen($shared) === 0) abort(500, 'ECDH derive failed');
        return $shared; // 32B
    }
}
// global helper
if (!function_exists('hex2bin')) {
    function hex2bin(string $h){
        return pack('H*', $h);
    }
}