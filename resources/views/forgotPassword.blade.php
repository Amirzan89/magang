<?php
    $tPath = app()->environment('local') ? '' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password</title>
    <link rel="stylesheet" href="{{ asset($tPath.'forgotPassword.css') }}">
</head>
<body>
    <div class="container">
        <h2>Lupa Password</h2>
        <form id="step1" class="active">
            <input type="email" placeholder="Email Anda" required>
            <button type="button" onclick="goToStep(2)">Kirim OTP</button>
        </form>

        <form id="step2">
            <input type="text" placeholder="Masukkan Kode OTP" required>
            <button type="button" onclick="goToStep(3)">Verifikasi</button>
            <div class="back" onclick="goToStep(1)">← Kembali</div>
        </form>

        {{-- Step 3: Ganti Password --}}
        <form id="step3">
            <input type="password" placeholder="Password Baru" required>
            <input type="password" placeholder="Ulangi Password" required>
            <button type="submit">Simpan Password</button>
            <div class="back" onclick="goToStep(2)">← Kembali</div>
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
    <script src="{{ asset($tPath.'forgotPassword.js') }}"></script>
</body>
</html>