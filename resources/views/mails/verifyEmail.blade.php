<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins">
    <style>
    </style>
</head>
<body>
    <div>
        <div class="bg"></div>
        <div class="content">
            <h1>Verify Email</h1>
            <span>Berikut ini adalah kode untuk verifikasi email anda</span>
            <div class="otp">
                <p>codee otp   {{ $code }}</p>
            </div>
            <p>atau link untuk verifikasi email anda <a href="{{$link.'?email='.$email}}">Verifikasi Email</a></p>
        </div>
    </div>
</body>
</html>