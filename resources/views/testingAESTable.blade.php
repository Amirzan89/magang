<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>AES Request Form</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 40px;
    }
    .container {
      max-width: 1000px;
      margin: auto;
      background: #fff;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h3 {
      text-align: center;
      margin-bottom: 25px;
    }
    .form-section {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }
    .column {
      flex: 1;
      min-width: 400px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-family: monospace;
      font-size: 14px;
    }

    .send-btn {
      display: block;
      margin: 30px auto 0;
      padding: 12px 30px;
      background-color: #1f4ab8;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: 0.2s ease;
    }

    .send-btn:hover {
      background-color: #0f3990;
    }
  </style>
</head>
<body>
  <form class="container" id="aesForm">
    <h3>SQL Request Form</h3>
    <div class="form-section">
      <!-- Left: User Credential -->
      <div class="column">
        <h4>User Credential</h4>
        <div class="form-group">
          <label for="url">url</label>
          <input type="text" id="url" value="http://sereg.alcorsys.com:8989/JDataClassQuery" />
        </div>
        <div class="form-group">
          <label for="apikey">apikey</label>
          <input type="text" id="apikey" value="06EAAA9D10BE3D4386D10144E267B681" readonly />
        </div>
        <div class="form-group">
          <label for="password">password</label>
          <input type="text" id="password" value="A9CCF340D9A490104AC5159B8E1CBXXX" />
        </div>
        <div class="form-group">
          <label for="uniqueid">uniqueid</label>
          <input type="text" id="uniqueid" value="JFKlnUZyyu0MzRqj" />
        </div>
        <div class="form-group">
          <label for="timestamp">timestamp</label>
          <input type="text" id="timestamp" value="2025/08/01 08:18:43" />
        </div>
      </div>

      <!-- Right: Message -->
      <div class="column">
        <h4>Message</h4>
        <div class="form-group">
          <label for="datacore">datacore</label>
          <input type="text" id="datacore" value="core_002" />
        </div>
        <div class="form-group">
          <label for="datasclas">datasclas</label>
          <input type="text" id="datasclas" value="wareHouse" />
        </div>
        <div class="form-group">
          <label for="recordsperpage">recordsperpage</label>
          <input type="text" id="recordsperpage" value="0" />
        </div>
        <div class="form-group">
          <label for="currentpage">currentpage</label>
          <input type="text" id="currentpage" value="0" />
        </div>
        <div class="form-group">
          <label for="condition">condition</label>
          <input type="text" id="condition" value="whtype='SL'" />
        </div>
        <div class="form-group">
          <label for="order">order</label>
          <input type="text" id="order" value="warehouse" />
        </div>
        <div class="form-group">
          <label for="recordcount">recordcount</label>
          <input type="text" id="recordcount" value="yes" />
        </div>
        <div class="form-group">
          <label for="fields">fields</label>
          <input type="text" id="fields" value="whcode, warehouse" />
        </div>
        <div class="form-group">
          <label for="userid">userid</label>
          <input type="text" id="userid" value="ganiadi@thepyxis.net" />
        </div>
        <div class="form-group">
          <label for="groupid">groupid</label>
          <input type="text" id="groupid" value="XCYTUA" />
        </div>
        <div class="form-group">
          <label for="businessid">businessid</label>
          <input type="text" id="businessid" value="PJLBBS" />
        </div>
      </div>
    </div>
    <!-- Send Button -->
    <button type="submit" class="send-btn">Send</button>
  </form>
  <div id="responseContainer" style="margin-top: 30px; display: none;">
    <h4>Result:</h4>
    <table id="resultTable" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="text-align:left; border-bottom: 2px solid #ccc;">Warehouse Code</th>
          <th style="text-align:left; border-bottom: 2px solid #ccc;">Warehouse Name</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <div id="recordCount" style="margin-top: 10px; font-weight: bold;"></div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
  <script>
    var csrfToken = "{{ csrf_token() }}";
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
    aesForm.onsubmit = function(event){
        event.preventDefault();
        const url = inpUrl.value;
        const apiKey = inpApiKey.value;
        const password = inpPassword.value;
        const uniqueid = inpUniqueID.value;
        const timestamp = inpTimestamp.value;

        const dataCore = inpDataCore.value;
        const datasclas = inpDataClass.value;
        const recordsperpage = inpRecordPage.value;
        const currentpage = inpCurrentPage.value;
        const condition = inpCondition.value;
        const order = inpOrder.value;
        const recordcount = inpRecordCount.value;
        const fields = inpFields.value;
        const userid = inpUserID.value;
        const groupid = inpGroupID.value;
        const businessid = inpBusinessID.value;
        if (url.trim() === '') {
          console.log('URL harus diisi !');
          return;
        }
        if (apiKey.trim() === '') {
          console.log('API Key harus diisi !');
          return;
        }
        if (password.trim() === '') {
          console.log('Password harus diisi !');
          return;
        }
        if (uniqueid.trim() === '') {
          console.log('Unique ID harus diisi !');
          return;
        }
        if (timestamp.trim() === '') {
          console.log('Timestamp harus diisi !');
          return;
        }

        if (dataCore.trim() === '') {
          console.log('Data Core harus diisi !');
          return;
        }
        if (datasclas.trim() === '') {
          console.log('Data Class harus diisi !');
          return;
        }
        if (recordsperpage.trim() === '') {
          console.log('Record per page harus diisi !');
          return;
        }
        if (currentpage.trim() === '') {
            console.log('Current page harus diisi !');
            return;
        }
        if (condition.trim() === '') {
            console.log('Condition harus diisi !');
            return;
        }
        if (order.trim() === '') {
          console.log('Order harus diisi !');
          return;
        }
        if (recordcount.trim() === '') {
          console.log('Record Count harus diisi !');
          return;
        }
        if (fields.trim() === '') {
          console.log('Fields harus diisi !');
          return;
        }
        if (userid.trim() === '') {
          console.log('User id harus diisi !');
          return;
        }
        if (groupid.trim() === '') {
          console.log('Group ID harus diisi !');
          return;
        }
        if (businessid.trim() === '') {
          console.log('Business ID harus diisi !');
          return;
        }
        var xhr = new XMLHttpRequest();
        var tableData = JSON.stringify({
          "datacore": inpDataCore.value,
          "dataclass": inpDataClass.value,
          "recordsperpage": inpRecordPage.value,
          "currentpageno": inpCurrentPage.value,
          "condition": inpCondition.value,
          "order": inpOrder.value,
          "recordcount": inpRecordCount.value,
          "fields": inpFields.value,
          "userid": inpUserID.value,
          "groupid": inpGroupID.value,
          "businessid": inpBusinessID.value
        });
        const ivServer = CryptoJS.enc.Utf8.parse(inpUniqueID.value);
        const encr = CryptoJS.AES.encrypt(tableData, keyServer, { iv: ivServer, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }).ciphertext.toString(CryptoJS.enc.Hex);
        var requestBody = {
          url: inpUrl.value,
          apikey: inpApiKey.value,
          uniqueid: inpUniqueID.value,
          timestamp: inpTimestamp.value,
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
                const responseDecrypted = CryptoJS.AES.decrypt({ ciphertext: CryptoJS.enc.Hex.parse(res.data) }, keyServer, { iv: ivServer });
                console.log(responseDecrypted.toString(CryptoJS.enc.Utf8));
              } else {
                console.log(JSON.parse(xhr.responseText));
              }
            }
        }
        return false; 
    }
    // sendBtn.addEventListener('click', () => {
    //   // Collect form values (you can automate this too)
    //   const data = {
    //     whcode: document.getElementById('whcode')?.value || null,
    //     warehouse: document.getElementById('warehouse')?.value || null
    //   };
    //   // MOCK response – replace this with actual fetch() to Laravel
    //   const mockResponse = {
    //     data: [
    //       { whcode: "BALE", warehouse: "BALE BANJAR" },
    //       { whcode: "COFE", warehouse: "COFFEE SHOP LEGIAN BEACH" },
    //       { whcode: "IUSLJW", warehouse: "POOL BAR" },
    //       { whcode: "K5TN2U", warehouse: "SIKU BAR" },
    //       { whcode: "GYLQLF", warehouse: "TEPPANAYAKI" }
    //     ],
    //     totalrecords: 5
    //   };
    //   renderTable(mockResponse); // render from mock
    //   // Uncomment below if sending real AJAX
    //   // sendData(data);
    // });
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
    // Optional: real data sender
    /*
    function sendData(formData) {
      fetch('/your-laravel-endpoint', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(formData)
      })
      .then(res => res.json())
      .then(data => {
        renderTable(data);
      })
      .catch(err => {
        console.error(err);
      });
    }
    */
  </script>
</body>
</html>
