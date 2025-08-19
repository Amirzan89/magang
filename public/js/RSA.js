function hexToU8(hex) {
    if (!/^[0-9a-fA-F]+$/.test(hex) || hex.length % 2) throw new Error('bad hex');
    const out = new Uint8Array(hex.length / 2);
    for (let i = 0; i < out.length; i++) out[i] = parseInt(hex.substr(i*2,2),16);
    return out;
}
async function genRsaPairSession(){
    const { publicKey, privateKey } = await crypto.subtle.generateKey({ name: 'RSA-OAEP', modulusLength: 2048, publicExponent: new Uint8Array([1,0,1]), hash: 'SHA-256' }, true,['encrypt','decrypt']);
    const pkcs8 = await crypto.subtle.exportKey('pkcs8', privateKey);
    sessionStorage.rsa_priv = btoa(String.fromCharCode(...new Uint8Array(pkcs8)));
    const spki = await crypto.subtle.exportKey('spki', publicKey);
    const pubB64 = btoa(String.fromCharCode(...new Uint8Array(spki)));
    return pubB64;
}
async function handshakeRSA(){
    try{
        const clientNonce = crypto.getRandomValues(new Uint8Array(16));
        const clientNonceB64 = btoa(String.fromCharCode(...clientNonce));
        const pubB64 = await genRsaPairSession();
        const res = await fetch('/handsake-rsa', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
            body: JSON.stringify({ clientPublicSpkiB64: pubB64, clientNonce: clientNonceB64 })
        }).then(r => r.json());
        const pkcs8 = Uint8Array.from(atob(sessionStorage.rsa_priv), c => c.charCodeAt(0)).buffer;
        const priv = await crypto.subtle.importKey('pkcs8', pkcs8, { name: 'RSA-OAEP', hash: 'SHA-256' }, false, ['decrypt']);
        // const wrapped = Uint8Array.from(atob(res.data.encKey), c => c.charCodeAt(0)).buffer;
        console.log('isii priv', priv)
        const wrapped = hexToU8(res.data.encKey).buffer;
        console.log('isii encc', wrapped)
        const result = new Uint8Array(await crypto.subtle.decrypt({ name: 'RSA-OAEP' }, priv, wrapped));
        console.log('resultt handsake', result)
        // parse payload: [aes32 | hmac32 | keyId16 | clientNonce16 | serverNonce16 | exp8]
        let off=0;
        const aes = result.slice(off, off+=32);
        const hmac = result.slice(off, off+=32);
        const keyIdBytes = result.slice(off, off+=16);
        const clientNonceEcho = result.slice(off, off+=16);
        const serverNonceBytes = result.slice(off, off+=16);
        const expMs = new DataView(result.buffer, off, 8).getBigUint64(0, false);
        if (btoa(String.fromCharCode(...clientNonce)) !== btoa(String.fromCharCode(...clientNonceEcho))) {
            throw new Error('nonce mismatch');
        }
        sessionStorage.sealed = res.data.sealed;
        sessionStorage.keyId = [...keyIdBytes].map(x=>x.toString(16).padStart(2,'0')).join('');
        sessionStorage.serverNonce = btoa(String.fromCharCode(...serverNonceBytes));
        sessionStorage.aes_key = [...aes].map(x=>x.toString(16).padStart(2,'0')).join('');
        sessionStorage.hmac_key = [...hmac].map(x=>x.toString(16).padStart(2,'0')).join('');
        sessionStorage.key_exp = expMs.toString();
        console.log('sealed', sessionStorage.sealed)
        console.log('keyId', sessionStorage.keyId)
        console.log('serverNonce', sessionStorage.serverNonce)
        console.log('aess', sessionStorage.aes_key)
        console.log('hmac_key', sessionStorage.hmac_key)
        console.log('key_exp', sessionStorage.key_exp)
    }catch(error){
        console.log('error', error);
    }
}
const hexCus = {
    enc: u8 => [...u8].map(b=>b.toString(16).padStart(2,'0')).join(''),
    dec: h  => new Uint8Array(h.match(/../g).map(x=>parseInt(x,16))),
};
const genIV = async(idUser) => {
    const encoder = new TextEncoder();
    const hmacKey = atob(sessionStorage.hmac_key);
    const cryptoKey = await crypto.subtle.importKey(
        "raw",
        typeof hmacKey === "string" ? encoder.encode(hmacKey) : hmacKey,
        { name: "HMAC", hash: "SHA-256" },
        false,
        ["sign"]
    );
    const timestamp = Date.now().toString();
    const random = btoa(String.fromCharCode(...crypto.getRandomValues(new Uint8Array(16))));
    const mergedInput = encoder.encode(idUser + timestamp + random);
    return new Uint8Array(await crypto.subtle.sign('HMAC', cryptoKey, mergedInput)).slice(0, 16);
}
const encryptReq = async (requestBody) => {
    const iv = await genIV();
    const cipherHex = CryptoJS.AES.encrypt(requestBody, CryptoJS.enc.Hex.parse(sessionStorage.aes_key), { iv: CryptoJS.enc.Hex.parse(hexCus.enc(iv)), mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).ciphertext.toString(CryptoJS.enc.Hex);
    const hmacKey = await crypto.subtle.importKey('raw', hexCus.dec(sessionStorage.hmac_key), { name:'HMAC', hash:'SHA-256' }, false, ['sign']);
    const payload = new Uint8Array(iv.length + (cipherHex.length/2));
    payload.set(iv, 0);
    payload.set(hexCus.dec(cipherHex), iv.length);
    const mac = new Uint8Array(await crypto.subtle.sign('HMAC', hmacKey, payload));
    return { iv: hexCus.enc(iv), data: cipherHex, mac: hexCus.enc(mac) };
}
const decryptRes = (cipher, iv) => {
    return CryptoJS.AES.decrypt({ ciphertext: CryptoJS.enc.Hex.parse(cipher) }, CryptoJS.enc.Hex.parse(sessionStorage.aes_key), { iv: CryptoJS.enc.Hex.parse(iv) }).toString(CryptoJS.enc.Utf8);
}