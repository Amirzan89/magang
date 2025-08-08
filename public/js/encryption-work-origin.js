const pingFirstTime = async () => {
    const response = await fetch('/fetch-token', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({}) // or null
    });
    if (!response.ok) {
        const error = await response.json();
        console.error('Ping failed', error);
        return;
    }
    const res = await response.json();
    console.log(res);
    sessionStorage.aes_key = CryptoJS.enc.Hex.parse(res.data.aes_key);
    sessionStorage.hmac_key = res.data.hmac_key;
    return { key: res.data.aes_key, iv:res.data.iv };
}
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
const encryptReq = async (requestBody, key, iv) => {
    const encrypted = CryptoJS.AES.encrypt(requestBody, CryptoJS.enc.Hex.parse(key), { iv: CryptoJS.enc.Hex.parse(iv), mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).ciphertext.toString(CryptoJS.enc.Hex);
    console.log('encrypted',encrypted)
    console.log('iv',iv)
    return encrypted;
}
const decryptRes = (cipher, iv) => {
    return CryptoJS.AES.decrypt({ ciphertext: CryptoJS.enc.Hex.parse(cipher) }, sessionStorage.aes_key, { iv: CryptoJS.enc.Hex.parse(iv) }).toString(CryptoJS.enc.Utf8);
}