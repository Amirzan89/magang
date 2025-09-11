<?php
use Illuminate\Support\Facades\Route;
Route::group(['middleware'=>'authMobile','authorized'], function(){
});