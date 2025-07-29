<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    
    <script>
        async function aesEncrypt(plaintext, base64Key) {
        const key = await crypto.subtle.importKey(
            "raw",
            Uint8Array.from(atob(base64Key), c => c.charCodeAt(0)),
            { name: "AES-CBC" },
            false,
            ["encrypt"]
        );

        const iv = crypto.getRandomValues(new Uint8Array(16));
        const encoded = new TextEncoder().encode(plaintext);

        const ciphertext = await crypto.subtle.encrypt({ name: "AES-CBC", iv }, key, encoded);
        const combined = new Uint8Array([...iv, ...new Uint8Array(ciphertext)]);
        return btoa(String.fromCharCode(...combined));
        }

        async function aesDecrypt(base64Ciphertext, base64Key) {
        const raw = Uint8Array.from(atob(base64Ciphertext), c => c.charCodeAt(0));
        const iv = raw.slice(0, 16);
        const ciphertext = raw.slice(16);

        const key = await crypto.subtle.importKey(
            "raw",
            Uint8Array.from(atob(base64Key), c => c.charCodeAt(0)),
            { name: "AES-CBC" },
            false,
            ["decrypt"]
        );

        const decrypted = await crypto.subtle.decrypt({ name: "AES-CBC", iv }, key, ciphertext);
        return new TextDecoder().decode(decrypted);
        }
    </script>
</body>
</html>