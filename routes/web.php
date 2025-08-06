<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\Services\Encryption\TestingSimpleController;
use App\Http\Controllers\Services\Encryption\TestingSessionController;
use App\Http\Controllers\Services\Encryption\TestingRSAController;
use App\Http\Controllers\Pages\HomeController;
Route::get('/', function () {
    return view('welcome');
});
Route::post('/por', );
Route::get('/view-aes', function () {
    return view('testingAES');
});
Route::get('/test/simple', [TestingSimpleController::class, 'tesss']);
Route::post('/test/simple-encrypt', [TestingSimpleController::class, 'testEncrypt']);
Route::post('/test/simple-decrypt', [TestingSimpleController::class, 'testDecrypt']);
Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::get('/test-view', function () {
    return view('testingSession');
});
Route::get('/pyxis-view', function () {
    return view('testingAESTable');
});
Route::post('/proxy-pyxis', function (Request $request) {
    $res = Http::post($request->input('url'), $request->except('url'))->json();
    return response()->json(['status' => $res['code'] ? 'success' : 'error', 'data' => $res['message']], $res['code']);
});

Route::get('/test/ping-session', [TestingSessionController::class, 'tes_ping']);
Route::post('/test/session', [TestingSessionController::class, 'tesss']);
Route::group(['middleware'=>'auth'],function(){
    Route::get('/', function () {
        return view('welcome');
    });
    // Route::get('/pond',function(){
    //     return view('uploadPond');
    // });
    // Route::get('/upload1',function(){
    //     return view('uploadForm');
    // });
    // Route::get('/upload1_old',function(){
    //     return view('upload1_old');
    // });
    // Route::get('/upload2',function(){
    //     return view('upload2');
    // });
    Route::get('/login',function(Request $request){
        if($request->wantsJson()){
            return response()->json(['status' => 'success', 'message' => 'OK']);
        }
        return view('login');
    })->name('login');
    Route::get('/register',function(Request $request){
        // if($request->wantsJson()){
        //     return response()->json(['status' => 'success', 'message' => 'OK']);
        // }
        return view('register');
    });
    // Route::get('/auth/redirect','Auth\LoginController@redirectToProvider');
    // Route::get('/auth/google', 'Auth\LoginController@handleGoogleLogin');
    // Route::post('/auth/google-tap', 'Auth\LoginController@handleGoogleLogin');
    // Route::group(["prefix"=>"/verify"],function(){
    //     Route::group(['prefix'=>'/create'],function(){
    //         Route::post('/password','Services\MailController@createForgotPassword');
    //         Route::post('/email','Services\MailController@createVerifyEmail');
    //     });
    //     Route::group(['prefix'=>'/password'],function(){
    //         Route::get('/{any?}','UserController@getChangePass')->where('any','.*');
    //         Route::post('/','UserController@changePassEmail');
    //     });
    //     Route::group(['prefix'=>'/email'],function(){
    //         Route::get('/{any?}','UserController@verifyEmail')->where('any','.*');
    //         Route::post('/','UserController@verifyEmail');
    //     });
    //     Route::group(['prefix'=>'/otp'],function(){
    //         Route::post('/resend/password','Services\MailController@ResendOTP');
    //         Route::post('/resend/email','Services\MailController@ResendOTP');
    //         Route::post('/password','UserController@getChangePass');
    //         Route::post('/email','UserController@verifyEmail');
    //     });
    // });
    Route::group(['prefix'=>'/users'], function(){
        Route::post('/login', [LoginController::class, 'login']);
        Route::post('/register', [RegisterController::class, 'register']);
        Route::post('/register/google', [UserController::class, 'createGoogleUser']);
        Route::post('/logout', [UserController::class, 'logout']);
        Route::group(['prefix'=>'/update'], function(){
            Route::post('/profile', [UserController::class, 'updateProfile']);
            Route::post('/password', [UserController::class, 'updatePassword']);
        });
    });
    // Route::group(['prefix'=>'/transaksi'], function(){
    //     Route::post('/midtrans/notify','Services\TransaksiController@createTransaksi');
    //     Route::post('/cancel','Services\TransaksiController@cancel');
    //     Route::post('/stop','Services\TransaksiController@stop');
    // });
    // Route::group(['prefix'=>'/upload'],function(){
    //     Route::post('/file','UploadController@uploadChunk');
    //     Route::post('/validate','UploadController@validation');
    //     Route::delete('/cancel','UploadController@cancelUpload');
    //     Route::delete('/delete','UploadController@deleteUpload');
    // });
});
Route::fallback(function(){
    $indexPath = public_path('dist/index.html');
    if (File::exists($indexPath)) {
        $htmlContent = File::get($indexPath);
        return response($htmlContent, 404);
    } else {
        // If the index.html file doesn't exist, return a 404 response
        return response()->json(['error' => 'Page not found'], 404);
    }
});