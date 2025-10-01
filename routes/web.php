<?php

use App\Http\Controllers\Services\EventController;
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

use App\Http\Controllers\Services\MailController;

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
Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::post('/test/session', [TestingSessionController::class, 'tesss']);
Route::post('/handshake', [RSAController::class, 'handshake_rsa']);
Route::post('/footer-mail', [MailController::class, 'sendMailFooter']);

Route::group(['prefix'=>'/verify'], function(){
    Route::group(['prefix'=>'/create'], function(){ 
        Route::post('/password', [MailController::class, 'createForgotPassword'])->withoutMiddleware(['auth', 'authorized']);
        Route::post('/email',[MailController::class, 'createVerifyEmail'])->withoutMiddleware(['auth', 'authorized']);
    });
    Route::group(['prefix'=>'/password'], function(){
        // Route::get('/{any?}', [UserController::class, 'getChangePass']'Mobile\PengelolaController@getChangePass')->where('any','.*')->withoutMiddleware(['auth', 'authorized']);
        // Route::post('/',[UserController::class, 'changePassEmail'])->withoutMiddleware(['auth', 'authorized']);
    });
    Route::group(['prefix'=>'/email'], function(){
        Route::get('/{any?}', [UserController::class, 'verifyEmail'])->where('any','.*')->withoutMiddleware(['auth', 'authorized']);
        Route::post('/',[UserController::class, 'verifyEmail'])->where('any','.*')->withoutMiddleware(['auth', 'authorized']);
    });
    Route::group(['prefix'=>'/otp'], function(){
        Route::post('/password', [UserController::class, 'getChangePass'])->withoutMiddleware(['auth', 'authorized']);
        Route::post('/email', [UserController::class, 'verifyEmail'])->withoutMiddleware(['auth', 'authorized']);
    });
});
Route::group(['prefix'=>'/users'], function(){
    Route::post('/login', [LoginController::class, 'login'])->withoutMiddleware(['auth', 'authorized']);
    Route::post('/login-google', [LoginController::class, 'loginGoogle'])->withoutMiddleware(['auth', 'authorized']);
    Route::post('/check-email', [RegisterController::class, 'checkEmailAvailability'])->withoutMiddleware(['auth', 'authorized']);
    Route::post('/register', [RegisterController::class, 'register'])->withoutMiddleware(['auth', 'authorized']);
    Route::group(['prefix'=>'/profile'], function(){
        Route::post('/', [UserController::class, 'getProfile']);
        Route::group(['prefix'=>'/profile'], function(){
            Route::post('/', [UserController::class, 'updateProfile']);
            Route::post('/uploadFoto', [UserController::class, 'uploadFoto'])->withoutMiddleware(['auth', 'authorized']);
            Route::put('/password', [UserController::class, 'updatePassword']);
        });
        Route::post('/foto', [UserController::class, 'checkFotoProfile']);
    });
    Route::post('/logout', [UserController::class, 'logout']);
});
Route::get('/', function(){
    return view('pages.home');
});
Route::post('/', [HomeController::class, 'showHome']);
Route::post('/about', [HomeController::class, 'showAbout']);
Route::post('/events', [HomeController::class, 'showEvents']);
Route::post('/search', [EventController::class, 'searchEvent']);
Route::post('/events-category', [HomeController::class, 'getEventCategory']);
Route::post('/events/{id}', [HomeController::class, 'showEventDetail']);
Route::post('/booking/{id}', [HomeController::class, 'showEventDetail']);
Route::post('/event-booking', [EventController::class, 'bookingEvent']);