<?php
  $tPath = app()->environment('local') ? '' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>AES Request Form</title>
  <link rel="stylesheet" href="{{ asset($tPath.'css/viewAESTable.css') }}">
</head>
<body>
  <script>
    const csrfToken = "{{ csrf_token() }}";
  </script>
  <button type="button"" class="send-btn" id="btn-submit">Send</button>
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
  <script src="{{ asset($tPath.'js/viewAESTable.js') }}"></script>
</body>
</html>