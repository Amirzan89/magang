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
    const data = await response.json();
    console.log(data);
    sessionStorage.aes_key = CryptoJS.enc.Hex.parse(data.data.aes_key);
    sessionStorage.aes_iv = CryptoJS.enc.Hex.parse(data.data.aes_iv);
    sessionStorage.aes_iv_raw = data.data.aes_iv;
    sessionStorage.hmac_key = data.data.hmac_key;
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
// return btoa(String.fromCharCode(new Uint8Array(await crypto.subtle.sign('HMAC', cryptoKey, mergedInput)).slice(0, 16)));
// return [...new Uint8Array(await crypto.subtle.sign('HMAC', cryptoKey, mergedInput)).slice(0, 16)].map(b => b.toString(16).padStart(2, "0")).join("");
const encryptReq = async (requestBody) => {
    const iv = await genIV();
    const ivHex = [...iv].map(b => b.toString(16).padStart(2, "0")).join("");
    // const encrypted = CryptoJS.AES.encrypt(requestBody, sessionStorage.aes_key, { iv: CryptoJS.enc.Hex.parse(ivHex), mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).ciphertext.toString(CryptoJS.enc.Hex);
    const encrypted = CryptoJS.AES.encrypt(requestBody, sessionStorage.aes_key, { iv: sessionStorage.aes_iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).ciphertext.toString(CryptoJS.enc.Hex);
    console.log('encrypted',encrypted)
    console.log('iv',ivHex)
    // return {data: encrypted + ivHex, key: sessionStorage.aes_key, iv: ivHex };
    return {data: encrypted + sessionStorage.aes_iv_raw, key: sessionStorage.aes_key, iv: sessionStorage.aes_iv_raw };
}
const decryptRes = (cipher, iv) => {
    return CryptoJS.AES.decrypt({ ciphertext: CryptoJS.enc.Hex.parse(cipher) }, sessionStorage.aes_key, { iv: sessionStorage.aes_iv }).toString(CryptoJS.enc.Utf8);
}