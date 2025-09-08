<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-widath, initial-scale=1.0">
    <title>Form Reset Password</title>
</head>
<body>
    <div class="bg">
        <div class="content">
            <h1></h1>
            <p>codee otp   {{ $code }}</p>
        </div>
        <div>
            <a href="{{$link.'?email='.$email}}">forgot password</a>
        </div>
    </div>
</body>
</html>