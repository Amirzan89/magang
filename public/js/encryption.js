const genIV = async(idUser) => {
  const encoder = new TextEncoder();
  const timestamp = Date.now().toString();
  const keyData = encoder.encode(keyString);
  const key = await crypto.subtle.importKey(
    "raw",
    keyData,
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"]
  );
  const random = btoa(String.fromCharCode(...crypto.getRandomValues(new Uint8Array(16))));
  // Siapkan pesan untuk di-HMAC
  const messageData = encoder.encode(idUser + timestamp + random);
  const signature = await crypto.subtle.sign("HMAC", key, messageData);

    const hmac = await crypto.subtle.sign('HMAC', cryptoKey, mergedInput);
    return new Uint8Array(hmac).slice(0, 16);
}