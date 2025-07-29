<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Testing Session</title>
</head>
<body>
    <button onclick="clickbtn()">clickbtn</button>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
    <script>
        var csrfToken = "{{ csrf_token() }}";
        var keyServer = null;
        var ivServer = null;
        async function getKey(){
            await fetch("/test/ping-session").then(res => res.json()).then(data => {
                keyServer = CryptoJS.enc.Hex.parse(data.data.key);
                ivServer  = CryptoJS.enc.Hex.parse(data.data.iv);
                console.log("Handshake berhasil, AES siap dipakai!");
            })
        }
        async function sendTest() { 
            const key = CryptoJS.enc.Hex.parse(keyServer);
            const iv = CryptoJS.enc.Hex.parse(ivServer);
            const encrypted = CryptoJS.AES.encrypt("data rahasia ikiiiii", keyServer, { iv: ivServer, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).toString();
            var xhr = new XMLHttpRequest();
            xhr.open('POST',"/test/session")
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({"ciphertext": encrypted }));
            xhr.onreadystatechange = function() {
                if (xhr.readyState == XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        const responseDecrypted = CryptoJS.AES.decrypt(response.data, keyServer, { iv: ivServer });
                        const plaintext = responseDecrypted.toString(CryptoJS.enc.Utf8);
                        console.log('hasil response', plaintext)
                    } else {
                        var response = JSON.parse(xhr.responseText);
                        console.log('errror', response);
                    }
                }
            }
        }
        async function clickbtn(){
            await getKey();
            await sendTest();
        }
    </script>
</body>
</html>