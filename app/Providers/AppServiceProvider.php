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
        RateLimiter::for('assessment-login', function (Request $request) use ($limited) {
            $key = hash('sha256', trim((string) $request->input('nip')));

            return [
                Limit::perMinute(5)->by('access:'.$key.':'.$request->ip())->response($limited),
                Limit::perHour(30)->by('access-nip:'.$key)->response($limited),
                Limit::perMinute(120)->by('access-ip:'.$request->ip())->response($limited),
            ];
        });
    }
}
