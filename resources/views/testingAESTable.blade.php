<?php
  $tPath = app()->environment('local') ? '' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>AES Request Form</title>
  <link rel="stylesheet" href="{{ asset($tPath.'testingAESTable.css') }}">
</head>
<body>
  <script>
    const csrfToken = "{{ csrf_token() }}";
  </script>
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
  <script src="{{ asset($tPath.'testingAESTable.js') }}"></script>
</body>
</html>