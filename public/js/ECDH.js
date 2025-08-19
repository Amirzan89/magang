const b64 = {
  enc: (u8) => btoa(String.fromCharCode(...u8)),
  dec: (s) => Uint8Array.from(atob(s), c => c.charCodeAt(0)),
};
const hex = {
  enc: (u8) => [...u8].map(b=>b.toString(16).padStart(2,'0')).join(''),
  dec: (h) => new Uint8Array(h.match(/../g).map(x=>parseInt(x,16))),
};

// --- ECDH Handshake (P-256) -> HKDF -> AES(32B), HMAC(32B) ---
async function handshakeECDH() {
  // 1) Client generate ECDH keypair (P-256)
  const clientKeys = await crypto.subtle.generateKey(
    { name: 'ECDH', namedCurve: 'P-256' },
    true, ['deriveBits']
  );
  // export public as SPKI (DER)
  const spki = new Uint8Array(await crypto.subtle.exportKey('spki', clientKeys.publicKey));
  const clientPubSpkiB64 = b64.enc(spki);

  // salt (nonce) untuk HKDF
  const salt = crypto.getRandomValues(new Uint8Array(16));
  const saltB64 = b64.enc(salt);

  // 2) kirim ke server, terima serverPublic (SPKI base64)
  const res = await fetch('/ecdh/handshake', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ clientPubSpkiB64: clientPubSpkiB64, saltB64 })
  }).then(r=>r.json());

  // 3) Import server public
  const serverPub = await crypto.subtle.importKey(
    'spki',
    b64.dec(res.serverPubSpkiB64),
    { name: 'ECDH', namedCurve: 'P-256' },
    false, []
  );

  // 4) ECDH derive shared secret (256-bit)
  const shared = new Uint8Array(
    await crypto.subtle.deriveBits({ name: 'ECDH', public: serverPub }, clientKeys.privateKey, 256)
  );

  // 5) HKDF-SHA256 -> 64B -> split (32B AES, 32B HMAC)
  //    (pakai WebCrypto HKDF via deriveBits)
  const hkdfBase = await crypto.subtle.importKey('raw', shared, 'HKDF', false, ['deriveBits']);
  const okm = new Uint8Array(
    await crypto.subtle.deriveBits(
      { name: 'HKDF', hash: 'SHA-256', salt: b64.dec(res.saltB64), info: new Uint8Array([0x76,0x31]) }, // "v1"
      hkdfBase,
      512 // 64 bytes
    )
  );
  const aesKeyBytes  = okm.slice(0, 32);
  const hmacKeyBytes = okm.slice(32, 64);

  // simpan (HEX) buat CryptoJS + Subtle HMAC
  sessionStorage.aes_key  = hex.enc(aesKeyBytes);
  sessionStorage.hmac_key = hex.enc(hmacKeyBytes);
}

// --- IV random 16B (CBC) ---
function genIV() {
  return crypto.getRandomValues(new Uint8Array(16));
}

// --- Encrypt-then-MAC (CBC + HMAC-SHA256 over IV||cipher) ---
async function encryptReq(plainText) {
  const iv = genIV();
  // AES-CBC encrypt (CryptoJS expect Hex keys)
  const cipherHex = CryptoJS.AES.encrypt(
    plainText,
    CryptoJS.enc.Hex.parse(sessionStorage.aes_key),
    {
      iv: CryptoJS.enc.Hex.parse(hex.enc(iv)),
      mode: CryptoJS.mode.CBC,
      padding: CryptoJS.pad.Pkcs7
    }
  ).ciphertext.toString(CryptoJS.enc.Hex);

  // HMAC over IV||cipher
  const hmacKey = await crypto.subtle.importKey('raw', hex.dec(sessionStorage.hmac_key), { name:'HMAC', hash:'SHA-256' }, false, ['sign']);
  const payload = new Uint8Array(iv.length + (cipherHex.length/2));
  payload.set(iv, 0);
  payload.set(hex.dec(cipherHex), iv.length);
  const mac = new Uint8Array(await crypto.subtle.sign('HMAC', hmacKey, payload));

  return { iv: hex.enc(iv), data: cipherHex, mac: hex.enc(mac) };
}

async function decryptRes(cipherHex, ivHex, macHex) {
  // 1) verify HMAC first
  const hmacKey = await crypto.subtle.importKey('raw', hex.dec(sessionStorage.hmac_key), { name:'HMAC', hash:'SHA-256' }, false, ['verify']);
  const iv = hex.dec(ivHex), ct = hex.dec(cipherHex), mac = hex.dec(macHex);
  const payload = new Uint8Array(iv.length + ct.length);
  payload.set(iv, 0); payload.set(ct, iv.length);
  const ok = await crypto.subtle.verify('HMAC', hmacKey, mac, payload);
  if (!ok) throw new Error('tampered');

  // 2) decrypt
  const pt = CryptoJS.AES.decrypt(
    { ciphertext: CryptoJS.enc.Hex.parse(cipherHex) },
    CryptoJS.enc.Hex.parse(sessionStorage.aes_key),
    { iv: CryptoJS.enc.Hex.parse(ivHex), mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }
  ).toString(CryptoJS.enc.Utf8);
  return pt;
}