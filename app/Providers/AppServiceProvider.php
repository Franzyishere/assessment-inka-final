<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $limited = function (Request $request, array $headers) {
            $seconds = (int) ($headers['Retry-After'] ?? 60);
            $message = "Terlalu banyak permintaan. Coba kembali dalam {$seconds} detik.";

            return $request->expectsJson()
                ? response()->json(['message' => $message], 429, $headers)
                : redirect()->back()->withErrors(['access' => $message])->withHeaders($headers);
        };
        RateLimiter::for('assessment-otp', function (Request $request) use ($limited) {
            $key = hash('sha256', (string) $request->route('token'));

            return [
                Limit::perMinute(5)->by('otp:'.$key.':'.$request->ip())->response($limited),
                Limit::perHour(10)->by('otp-hour:'.$key)->response($limited),
                Limit::perMinute(120)->by('otp-ip:'.$request->ip())->response($limited),
            ];
        });
        RateLimiter::for('assessment-verify', function (Request $request) use ($limited) {
            return Limit::perMinute(10)
                ->by('verify:'.hash('sha256', (string) $request->route('token')).':'.$request->ip())->response($limited);
        });
    }
}
