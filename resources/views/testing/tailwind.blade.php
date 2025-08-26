<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body x-data="{ diclick() { console.log('kenek') }, current_time: '{{ now() }}' }">
    <div class="d-card d-w-96 bg-base-100 d-shadow-xl">
        <figure>
        </figure>
        <div class="d-card-body">
            <h2 class="d-card-title">My Project!</h2>
            <p>This is a description of my awesome new project built with Laravel, Tailwind, and DaisyUI.</p>
            <div class="d-card-actions justify-end">
                <button class="d-btn d-btn-primary" x-on:click="diclick">Learn More</button>
            </div>
            <h3 x-text="current_time"></h3>
        </div>
    </div>
</body>
</html>