<?php

use App\Http\Controllers\Services\EventController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\AdminController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController as AdminControllerServices;

use App\Http\Controllers\Services\MailController;
use App\Http\Controllers\Services\Encryption\TestingSimpleController;
use App\Http\Controllers\Services\Encryption\TestingSessionController;
use App\Http\Controllers\Security\RSAController;

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


Route::group(['middleware'=>['auth']], function(){
    Route::get('/', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/about', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/events', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/search', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/event/{id}', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/booking/{id}', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/event-booked', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/login', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/dashboard', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/event-booked', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::get('/profile', function(Request $request){
        return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
    });
    Route::middleware('auth')->get('/check-auth', fn() => response()->noContent());

    Route::group(['prefix'=>'/api'], function(){
        Route::post('/handshake', [RSAController::class, 'handshake_rsa']);
        Route::post('/footer-mail', [MailController::class, 'sendMailFooter']);
        Route::post('/', [HomeController::class, 'showHome']);
        Route::post('/about', [HomeController::class, 'showAbout']);
        Route::post('/events', [HomeController::class, 'showEvents']);
        Route::post('/search', [EventController::class, 'searchEvent']);
        Route::post('/event-categories', [HomeController::class, 'getEventCategory']);
        Route::post('/event/{id}', [HomeController::class, 'showEventDetail']);
        Route::post('/booking/{id}', [HomeController::class, 'showEventDetail']);
        Route::post('/event-booking', [EventController::class, 'bookingEvent']);
        Route::post('/dashboard', [AdminController::class, 'showDashboard']);
        Route::post('/event-booked', [AdminController::class, 'showEVentBooked']);
        Route::post('/profile', [AdminController::class, 'showProfile']);
        Route::group(['prefix'=>'/verify'], function(){
            Route::group(['prefix'=>'/create'], function(){ 
                Route::post('/password', [MailController::class, 'createForgotPassword'])->withoutMiddleware(['auth']);
                Route::post('/email',[MailController::class, 'createVerifyEmail'])->withoutMiddleware(['auth']);
            });
            Route::group(['prefix'=>'/password'], function(){
                Route::get('/{any?}', [AdminControllerServices::class, 'getChangePass'])->where('any','.*')->withoutMiddleware(['auth']);
                Route::post('/',[AdminControllerServices::class, 'changePassEmail'])->withoutMiddleware(['auth']);
            });
            Route::group(['prefix'=>'/email'], function(){
                Route::get('/{any?}', [AdminControllerServices::class, 'verifyEmail'])->where('any','.*')->withoutMiddleware(['auth']);
                Route::post('/',[AdminControllerServices::class, 'verifyEmail'])->where('any','.*')->withoutMiddleware(['auth']);
            });
            Route::group(['prefix'=>'/otp'], function(){
                Route::post('/password', [AdminControllerServices::class, 'getChangePass'])->withoutMiddleware(['auth']);
                Route::post('/email', [AdminControllerServices::class, 'verifyEmail'])->withoutMiddleware(['auth']);
            });
        });
        Route::group(['prefix'=>'/admin'], function(){
            Route::post('/login', [LoginController::class, 'login'])->withoutMiddleware(['auth']);
            Route::post('/login-google', [LoginController::class, 'loginGoogle'])->withoutMiddleware(['auth']);
            Route::group(['prefix'=>'/update'], function(){
                Route::put('/profile', [AdminControllerServices::class, 'updateProfile']);
                Route::put('/password', [AdminControllerServices::class, 'updatePassword']);
            });
            Route::get('/download/foto-profile', [AdminControllerServices::class, 'getFotoProfile']);
            Route::post('/logout', [AdminControllerServices::class, 'logout']);
        });
    });
});
Route::fallback(function(Request $request){
    return UtilityController::getView('', [], $request->wantsJson() ? 'json' : ['cond'=> ['view', 'redirect'], 'redirect' => '/' . $request->path()]);
});