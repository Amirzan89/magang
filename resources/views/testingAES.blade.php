<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>AES Encryption/Decryption</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
        }
        textarea, input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        button {
            padding: 10px 25px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            transition: background-color 0.2s ease-in-out;
        }
        button:hover {
            background-color: #0056b3;
        }
        .output-box {
            background: #eee;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <form class="container" id="aesForm">
        <h2>AES Encryption/Decryption</h2>
        <label for="inputText">Input Text</label>
        <textarea id="inputText" rows="4" placeholder="Enter plaintext or ciphertext here..."></textarea>
        <label for="key">Key</label>
        <input type="text" id="key" placeholder="Enter key (e.g. 1234567890abcdef)" />
        <label for="iv">IV (for CBC mode, optional)</label>
        <input type="text" id="iv" placeholder="Enter IV (e.g. abcdef1234567890)" />
        <label for="mode">AES Mode</label>
        <select id="mode">
            <option value="CBC">CBC</option>
            <option value="ECB">ECB</option>
        </select>
        <label for="padding">Padding</label>
        <select id="padding">
            <option value="PKCS5Padding">PKCS5</option>
            <option value="NoPadding">No Padding</option>
        </select>
        <div class="buttons">
            <button type="submit" id="encryptBtn">Encrypt</button>
            <button type="submit" id="decryptBtn">Decrypt</button>
        </div>
        <label for="output">Output</label>
        <div class="output-box" id="output">[output will go here]</div>
    </form>
    <script>
        var csrfToken = "{{ csrf_token() }}";
        const inpText = document.getElementById('inputText');
        const inpKey = document.getElementById('key');
        const inpIV = document.getElementById('iv');
        const inpMode = document.getElementById('mode');
        const inpPadding = document.getElementById('padding');
        const aesForm = document.getElementById('aesForm');
        const encryptBtn = document.getElementById('encryptBtn');
        const decryptBtn = document.getElementById('decryptBtn');
        const outputBox = document.getElementById('output');
        var url = null;
        var requestBody = null;
        var type = null;
        encryptBtn.onclick = () => {
            type = 'Enkripsi';
            url = '/test/simple-encrypt';
            requestBody = {
                input: inpText.value,
                key: inpKey.value,
                iv: inpIV.value,
            };
        };
        decryptBtn.onclick = () => {
            type = 'Dekripsi';
            requestBody = {
                chiper: inpText.value,
                key: inpKey.value,
                iv: inpIV.value,
            };
            url = '/test/simple-decrypt';
        };
        aesForm.onsubmit = function(event){
            event.preventDefault();
            const inputText = inpText.value;
            const key = inpKey.value;
            const iv = inpIV.value;
            if (inputText.trim() === '') {
                console.log('Input Text harus diisi !');
                return;
            }
            if (key.trim() === '') {
                console.log('Key harus diisi !');
                return;
            }
            if (iv.trim() === '') {
                console.log('IV harus diisi !');
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url)
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify(requestBody));
            xhr.onreadystatechange = function() {
                if (xhr.readyState == XMLHttpRequest.DONE) {
                    const res = JSON.parse(xhr.responseText);
                    outputBox.textContent = `Simulated ${type}:
                    - Text: ${inputText}
                    - Key: ${key}
                    - IV: ${iv}
                    - Mode: ${mode}
                    - Padding: ${padding}
                    - Result : ${res.data}`;
                }
            }
            return false; 
        }
    </script>
</body>
</html>