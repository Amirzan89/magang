<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestingSimpleController;
Route::get('/', function () {
    return view('welcome');
});
Route::post('/por', );
Route::get('/test', [TestingSimpleController::class, 'tesss']);