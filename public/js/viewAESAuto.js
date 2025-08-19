function renderTable(response) {
    const container = document.getElementById('responseContainer');
    const tbody = document.querySelector('#resultTable tbody');
    const count = document.getElementById('recordCount');
    tbody.innerHTML = '';
    if (response.data && Array.isArray(response.data)) {
        response.data.forEach(item => {
        const row = document.createElement('tr');
        const cell1 = document.createElement('td');
        cell1.textContent = item.whcode?.trim() || '-';
        cell1.style.padding = '8px';
        cell1.style.borderBottom = '1px solid #ddd';
        const cell2 = document.createElement('td');
        cell2.textContent = item.warehouse?.trim() || '-';
        cell2.style.padding = '8px';
        cell2.style.borderBottom = '1px solid #ddd';
        row.appendChild(cell1);
        row.appendChild(cell2);
        tbody.appendChild(row);
        });
        count.textContent = 'Total Records: ' + (response.totalrecords || response.data.length);
        container.style.display = 'block';
    } else {
        tbody.innerHTML = '<tr><td colspan="2">No data found</td></tr>';
        container.style.display = 'block';
    }
}
document.addEventListener("DOMContentLoaded", () => {
    var xhr = new XMLHttpRequest();
    var tableData = JSON.stringify({
        "datacore": "core_002",
        "dataclass": "wareHouse",
        "recordsperpage": "0",
        "currentpageno": "0",
        "condition": "whtype='SL'",
        "order": "warehouse",
        "recordcount": "yes",
        "fields": "whcode, warehouse",
        "userid": "ganiadi@thepyxis.net",
        "groupid": "XCYTUA",
        "businessid": "PJLBBS"
    });
    const ivServer = CryptoJS.enc.Utf8.parse("JFKlnUZyyu0MzRqj");
    const encr = CryptoJS.AES.encrypt(tableData, keyServer, { iv: ivServer, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).ciphertext.toString(CryptoJS.enc.Hex);
    var requestBody = {
        url: "http://sereg.alcorsys.com:8989/JDataClassQuery",
        apikey: "06EAAA9D10BE3D4386D10144E267B681",
        uniqueid: "JFKlnUZyyu0MzRqj",
        timestamp: "2025/08/01 08:18:43",
        message: encr,
    }
    xhr.open('POST', '/proxy-pyxis')
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(requestBody));
    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                const res = JSON.parse(xhr.responseText);
                const responseDecrypted = CryptoJS.AES.decrypt({ ciphertext: CryptoJS.enc.Hex.parse(res.data) }, keyServer, { iv: ivServer }).toString(CryptoJS.enc.Utf8);
                renderTable(JSON.parse(responseDecrypted))
            } else {
                console.log(JSON.parse(xhr.responseText));
            }
        }
    }
    return false;
})