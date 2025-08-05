<?php
    $tPath = app()->environment('local') ? '' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="{{ asset($tPath.'register.css') }}">
</head>
<body>
    <div class="container">
        <div id="register-form" class="fade">
            <h2>Register</h2>
            <form onsubmit="handleRegister(event)">
                <input type="text" placeholder="Name" required>
                <input type="email" placeholder="Email" required>
                <input type="password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>
        </div>

        <div id="verify-form" class="fade hidden">
            <h2>Email Verification</h2>
            <form onsubmit="handleVerification(event)">
                <input type="text" placeholder="Enter verification code" required>
                <button type="submit">Verify</button>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
    <script src="{{ asset($tPath.'register.js') }}"></script>
</body>
</html>