const pingFirstTime = async () => {
    const response = await fetch('/fetch-token', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    });
    if (!response.ok) {
        const error = await response.json();
        console.error('Ping failed', error);
        return;
    }
    const res = await response.json();
    sessionStorage.aes_key = res.data.aes_key;
    sessionStorage.hmac_key = res.data.hmac_key;
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
const encryptReq = async (requestBody) => {
    const ivHex = [...await genIV()].map(b => b.toString(16).padStart(2, "0")).join("");
    const encrypted = CryptoJS.AES.encrypt(requestBody, CryptoJS.enc.Hex.parse(sessionStorage.aes_key), { iv: CryptoJS.enc.Hex.parse(ivHex), mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).ciphertext.toString(CryptoJS.enc.Hex);
    return { data: encrypted, iv: ivHex };
}
const decryptRes = (cipher, iv) => {
    return CryptoJS.AES.decrypt({ ciphertext: CryptoJS.enc.Hex.parse(cipher) }, CryptoJS.enc.Hex.parse(sessionStorage.aes_key), { iv: CryptoJS.enc.Hex.parse(iv) }).toString(CryptoJS.enc.Utf8);
}