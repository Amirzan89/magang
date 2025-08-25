<?php
    $tPath = app()->environment('local') ? '' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset($tPath.'css/login.css') }}">
</head>
<body>
    <script>
        const csrfToken = "{{ csrf_token() }}";
    </script>
    <div class="login-container">
        <h2>Login</h2>
        <form id="loginForm" method="POST" action="/users/login">
            @csrf
            <label for="email">Email</label>
            <input type="text" id="inpEmail" name="email" required>
            <label for="password">Password</label>
            <input type="password" id="inpPassword" name="password" required>
            <div id="error" class="error-message" style="display:none;"></div>
            <button type="submit">Login</button>
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
    <script src="{{ asset($tPath.'js/encryption.js') }}"></script>
    <script src="{{ asset($tPath.'js/login.js') }}"></script>
</body>
</html>