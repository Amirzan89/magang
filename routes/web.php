<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\Services\Encryption\TestingSimpleController;
use App\Http\Controllers\Services\Encryption\TestingSessionController;
use App\Http\Controllers\Security\RSAController;

use App\Http\Controllers\Pages\HomeController;

use App\Http\Controllers\Services\EmailController;

// use App\Http\Controllers\Security\AESController;

Route::get('/view-aes', function(){
    return view('testing.testingAES');
});
Route::group(['prefix'=>'/test'], function(){
    Route::get('/simple', [TestingSimpleController::class, 'tesss']);
    Route::post('/simple-encrypt', [TestingSimpleController::class, 'testEncrypt']);
    Route::post('/simple-decrypt', [TestingSimpleController::class, 'testDecrypt']);
    Route::get('/ping-session', [TestingSessionController::class, 'tes_ping']);
    Route::get('/view', function(){
        return view('testing.testingSession');
    });
});
Route::group(['prefix'=>'/pyxis'], function(){
    Route::get('/testing', function(){
        return view('testing.testingAESTable');
    });
    Route::get('/view', function(){
        return view('viewAESTable');
    });
    
    Route::get('/auto', function(){
        return view('viewAESAuto');
    });
    Route::get('/auto-js', function(){
        return view('viewAESAutoJS');
    });
    Route::post('/proxy', function(Request $request){
        $res = Http::post($request->input('url'), $request->except('url'))->json();
        return response()->json(['status' => $res['code'] ? 'success' : 'error', 'data' => $res['message']], $res['code']);
    });
    Route::get('/test-query', function(){
        return view('testing.testingQueryRSA');
    });
    Route::post('/query-rsa', [RSAController::class, 'query_rsa']);
});
Route::get('/tailwind', function(){
    return view('testing.tailwind');
});
Route::post('/handshake-rsa', [RSAController::class, 'handshake_rsa']);
Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::post('/test/session', [TestingSessionController::class, 'tesss']);
Route::post('/handshake-rsa', [RSAController::class, 'handshake_rsa']);
Route::post('/footer-mail', [EmailController::class, 'sendEmailFooter']);

Route::get('/', function(){
    return view('pages.home');
});
Route::post('/', [HomeController::class, 'showHome']);