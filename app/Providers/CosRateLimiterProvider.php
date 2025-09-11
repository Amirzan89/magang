<?php

namespace App\Providers;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
class CosRateLimiterProvider extends ServiceProvider
{
    public static function configure()
    {
        RateLimiter::for('admin', function(Request $request){
            return Limit::perMinute(50)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('rekap', function(Request $request){
            return Limit::perMinute(25)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('users', function(Request $request){
            return Limit::perMinute(50)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('acara', function(Request $request){
            return Limit::perMinute(50)->by($request->user()?->id ?: $request->ip());
        });
    }
}