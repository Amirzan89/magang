<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

use App\Http\Controllers\Services\Encryption\TestingSimpleController;
use App\Http\Controllers\Services\Encryption\TestingSessionController;
use App\Http\Controllers\Services\Encryption\TestingRSAController;
// use App\Http\Controllers\Services\Encryption\TestingRSAController;
use App\Http\Controllers\Pages\HomeController;

use App\Http\Controllers\Security\AESController;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/por', );
Route::get('/view-aes', function (){
    return view('testingAES');
});
Route::post('/fetch-token', [AESController::class, 'FirstTime']);
Route::get('/test/simple', [TestingSimpleController::class, 'tesss']);
Route::post('/test/simple-encrypt', [TestingSimpleController::class, 'testEncrypt']);
Route::post('/test/simple-decrypt', [TestingSimpleController::class, 'testDecrypt']);
Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::get('/test-view', function () {
    return view('testingSession');
});
Route::group(['prefix'=>'/pyxis'], function(){
    Route::get('/testing', function () {
        return view('testingAESTable');
    });
    Route::get('/view', function () {
        return view('viewAESTable');
    });
    
    Route::get('/auto', function () {
        return view('viewAESAuto');
    });
    Route::get('/auto-js', function () {
        return view('viewAESAutoJS');
    });
    Route::post('/proxy', function (Request $request){
        $res = Http::post($request->input('url'), $request->except('url'))->json();
        return response()->json(['status' => $res['code'] ? 'success' : 'error', 'data' => $res['message']], $res['code']);
    });
    Route::get('/handsake-rsa', [TestingSimpleController::class, 'handsake_rsa']);
    Route::get('/handsake-ecdh', [TestingSimpleController::class, 'handsake_ecdh']);
    Route::get('/query-rsa', [TestingSimpleController::class, 'query_rsa']);
    Route::get('/query-ecdh', [TestingSimpleController::class, 'query_ecdh']);
});

Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::post('/test/session', [TestingSessionController::class, 'tesss']);