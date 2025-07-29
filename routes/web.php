<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestingSimpleController;
use App\Http\Controllers\TestingSessionController;
Route::get('/', function () {
    return view('welcome');
});
Route::post('/por', );
Route::get('/test/simple', [TestingSimpleController::class, 'tesss']);
Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::get('/test-view', function () {
    return view('testingSession');
});
Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::post('/test/session', [TestingSessionController::class, 'tesss']);