<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

use App\Http\Controllers\Services\Encryption\TestingSimpleController;
use App\Http\Controllers\Services\Encryption\TestingSessionController;
use App\Http\Controllers\Services\Encryption\TestingRSAController;
// use App\Http\Controllers\Services\Encryption\TestingRSAController;
use App\Http\Controllers\Pages\HomeController;

// use App\Http\Controllers\Security\AESController;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/por', );
Route::get('/view-aes', function (){
    return view('testingAES');
});
// Route::post('/fetch-token', [AESController::class, 'FirstTime']);
Route::group(['prefix'=>'/test'], function(){
    Route::get('/simple', [TestingSimpleController::class, 'tesss']);
    Route::post('/simple-encrypt', [TestingSimpleController::class, 'testEncrypt']);
    Route::post('/simple-decrypt', [TestingSimpleController::class, 'testDecrypt']);
    Route::get('/ping-session', [TestingSessionController::class, 'tes_ping']);
    Route::get('/view', function (){
        return view('testingSession');
    });
});
Route::group(['prefix'=>'/pyxis'], function(){
    Route::get('/testing', function (){
        return view('testingAESTable');
    });
    Route::get('/view', function (){
        return view('viewAESTable');
    });
    
    Route::get('/auto', function (){
        return view('viewAESAuto');
    });
    Route::get('/auto-js', function (){
        return view('viewAESAutoJS');
    });
    Route::post('/proxy', function (Request $request){
        $res = Http::post($request->input('url'), $request->except('url'))->json();
        return response()->json(['status' => $res['code'] ? 'success' : 'error', 'data' => $res['message']], $res['code']);
    });
    Route::get('/test-query', function (){
        return view('testingQueryRSA');
    });
    Route::post('/query-rsa', [TestingRSAController::class, 'query_rsa']);
});
Route::post('/handshake-rsa', [TestingRSAController::class, 'handshake_rsa']);
Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::post('/test/session', [TestingSessionController::class, 'tesss']);