// function renderTable(response) {
//     const container = document.getElementById('responseContainer');
//     const tbody = document.querySelector('#resultTable tbody');
//     const count = document.getElementById('recordCount');
//     tbody.innerHTML = '';
//     if (response.data && Array.isArray(response.data)) {
//         response.data.forEach(item => {
//         const row = document.createElement('tr');
//         const cell1 = document.createElement('td');
//         cell1.textContent = item.whcode?.trim() || '-';
//         cell1.style.padding = '8px';
//         cell1.style.borderBottom = '1px solid #ddd';
//         const cell2 = document.createElement('td');
//         cell2.textContent = item.warehouse?.trim() || '-';
//         cell2.style.padding = '8px';
//         cell2.style.borderBottom = '1px solid #ddd';
//         row.appendChild(cell1);
//         row.appendChild(cell2);
//         tbody.appendChild(row);
//         });
//         count.textContent = 'Total Records: ' + (response.totalrecords || response.data.length);
//         container.style.display = 'block';
//     } else {
//         tbody.innerHTML = '<tr><td colspan="2">No data found</td></tr>';
//         container.style.display = 'block';
//     }
// }
document.addEventListener('DOMContentLoaded', async() => {
    var xhr = new XMLHttpRequest();
    var tableData = JSON.stringify({
        "userid": "demo@demo.com",
        "groupid": "XCYTUA",
        "businessid": "PJLBBS",
        "sql": "SELECT id, keybusinessgroup, keyregistered, eventgroup, eventid, eventname, eventdescription, startdate, enddate, quota, price, inclusion, imageicon_1, imageicon_2, imageicon_3, imageicon_4, imageicon_5, imageicon_6, imageicon_7, imageicon_8, imageicon_9 FROM event_schedule",
        "order": ""
    });
    if((sessionStorage.aes_key == undefined) && (sessionStorage.hmac_key == undefined)){
        await handshakeRSA();
    }
    const encr = await encryptReq(tableData);
    var requestBody = {
        uniqueid: encr.iv,
        chiper: encr.data,
        mac: encr.mac,
    }
    xhr.open('POST', '/pyxis/query-rsa')
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('X-Merseal', sessionStorage.merseal);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(requestBody));
    xhr.onreadystatechange = async function(){
        if(xhr.readyState == XMLHttpRequest.DONE){
            if(xhr.status === 200){
                const res = JSON.parse(xhr.responseText);
                const responseDecrypted = await decryptRes(res.data, encr.iv);
                console.log('hasil decrypt response ', responseDecrypted)
                // renderTable(JSON.parse(responseDecrypted))
            }else{
                console.log(JSON.parse(xhr.responseText));
            }
        }
    }
    return false;
});