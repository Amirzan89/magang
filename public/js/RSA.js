async function genRsaPairSession() {
    const { publicKey, privateKey } = await crypto.subtle.generateKey({ name: 'RSA-OAEP', modulusLength: 2048, publicExponent: new Uint8Array([1,0,1]), hash: 'SHA-256' }, true,['encrypt','decrypt']);
    const pkcs8 = await crypto.subtle.exportKey('pkcs8', privateKey);
    sessionStorage.rsa_priv = btoa(String.fromCharCode(...new Uint8Array(pkcs8)));
    const spki = await crypto.subtle.exportKey('spki', publicKey);
    const pubB64 = btoa(String.fromCharCode(...new Uint8Array(spki)));
    return pubB64;
}
async function pingFirstTime() {
    const clientNonce = crypto.getRandomValues(new Uint8Array(16));
    const clientNonceB64 = btoa(String.fromCharCode(...clientNonce));
    const pubB64 = await genRsaPairSession();
    const res = await fetch('/fetch-token', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
        body: JSON.stringify({ clientPublicSpkiB64: pubB64, clientNonce: clientNonceB64 })
    }).then(r => r.json());
    const pkcs8 = Uint8Array.from(atob(sessionStorage.rsa_priv), c => c.charCodeAt(0)).buffer;
    const priv = await crypto.subtle.importKey('pkcs8', pkcs8, { name: 'RSA-OAEP', hash: 'SHA-256' }, false, ['decrypt']);
    const wrapped = Uint8Array.from(atob(res.encKey), c => c.charCodeAt(0)).buffer;
    const plain = new Uint8Array(await crypto.subtle.decrypt({ name: 'RSA-OAEP' }, priv, wrapped));
    // parse payload: [aes32 | hmac32 | keyId16 | clientNonce16 | serverNonce16 | exp8]
    let off=0;
    const aes = plain.slice(off, off+=32);
    const hmac = plain.slice(off, off+=32);
    const keyIdBytes = plain.slice(off, off+=16);
    const clientNonceEcho = plain.slice(off, off+=16);
    const serverNonceBytes = plain.slice(off, off+=16);
    const expMs = new DataView(plain.buffer, off, 8).getBigUint64(0, false);
    if (btoa(String.fromCharCode(...clientNonce)) !== btoa(String.fromCharCode(...clientNonceEcho))) {
        throw new Error('nonce mismatch');
    }
    sessionStorage.keyId = [...keyIdBytes].map(x=>x.toString(16).padStart(2,'0')).join('');
    sessionStorage.serverNonce = btoa(String.fromCharCode(...serverNonceBytes));
    sessionStorage.aes_key = [...aes].map(x=>x.toString(16).padStart(2,'0')).join('');
    sessionStorage.hmac_key = [...hmac].map(x=>x.toString(16).padStart(2,'0')).join('');
    sessionStorage.key_exp = expMs.toString();
}