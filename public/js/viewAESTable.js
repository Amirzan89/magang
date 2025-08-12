const inpUrl = document.getElementById('url');
const inpApiKey = document.getElementById('apikey');
const inpPassword = document.getElementById('password');
const inpUniqueID = document.getElementById('uniqueid');
const inpTimestamp = document.getElementById('timestamp');

const inpDataCore = document.getElementById('datacore');
const inpDataClass = document.getElementById('datasclas');
const inpRecordPage = document.getElementById('recordsperpage');
const inpCurrentPage = document.getElementById('currentpage');
const inpCondition = document.getElementById('condition');
const inpOrder = document.getElementById('order');
const inpRecordCount = document.getElementById('recordcount');
const inpFields = document.getElementById('fields');
const inpUserID = document.getElementById('userid');
const inpGroupID = document.getElementById('groupid');
const inpBusinessID = document.getElementById('businessid');
const aesForm = document.getElementById('aesForm');
const keyServer = CryptoJS.enc.Utf8.parse("A9CCF340D9A490104AC5159B8E1CBXXX");
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
// document.getElementById('btn-submit').onclick = function(){
document.addEventListener("DOMContentLoaded", () => {
    // const url = inpUrl.value;
    // const apiKey = inpApiKey.value;
    // const password = inpPassword.value;
    // const uniqueid = inpUniqueID.value;
    // const timestamp = inpTimestamp.value;

    // const dataCore = inpDataCore.value;
    // const datasclas = inpDataClass.value;
    // const recordsperpage = inpRecordPage.value;
    // const currentpage = inpCurrentPage.value;
    // const condition = inpCondition.value;
    // const order = inpOrder.value;
    // const recordcount = inpRecordCount.value;
    // const fields = inpFields.value;
    // const userid = inpUserID.value;
    // const groupid = inpGroupID.value;
    // const businessid = inpBusinessID.value;
    // if (url.trim() === '') {
    //     console.log('URL harus diisi !');
    //     return;
    // }
    // if (apiKey.trim() === '') {
    //     console.log('API Key harus diisi !');
    //     return;
    // }
    // if (password.trim() === '') {
    //     console.log('Password harus diisi !');
    //     return;
    // }
    // if (uniqueid.trim() === '') {
    //     console.log('Unique ID harus diisi !');
    //     return;
    // }
    // if (timestamp.trim() === '') {
    //     console.log('Timestamp harus diisi !');
    //     return;
    // }

    // if (dataCore.trim() === '') {
    //     console.log('Data Core harus diisi !');
    //     return;
    // }
    // if (datasclas.trim() === '') {
    //     console.log('Data Class harus diisi !');
    //     return;
    // }
    // if (recordsperpage.trim() === '') {
    //     console.log('Record per page harus diisi !');
    //     return;
    // }
    // if (currentpage.trim() === '') {
    //     console.log('Current page harus diisi !');
    //     return;
    // }
    // if (condition.trim() === '') {
    //     console.log('Condition harus diisi !');
    //     return;
    // }
    // if (order.trim() === '') {
    //     console.log('Order harus diisi !');
    //     return;
    // }
    // if (recordcount.trim() === '') {
    //     console.log('Record Count harus diisi !');
    //     return;
    // }
    // if (fields.trim() === '') {
    //     console.log('Fields harus diisi !');
    //     return;
    // }
    // if (userid.trim() === '') {
    //     console.log('User id harus diisi !');
    //     return;
    // }
    // if (groupid.trim() === '') {
    //     console.log('Group ID harus diisi !');
    //     return;
    // }
    // if (businessid.trim() === '') {
    //     console.log('Business ID harus diisi !');
    //     return;
    // }
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