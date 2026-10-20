<?php

namespace App\Http\Middleware;

use App\Models\AssessmentAccessCode;
use App\Services\AssessmentAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureParticipantAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role !== 'peserta_assessment' || $request->routeIs('logout')) {
            return $next($request);
        }
        $code = AssessmentAccessCode::with('participant.user', 'participant.program')->find($request->session()->get('assessment_access_id'));
        $message = 'Akses peserta tidak aktif. Masuk menggunakan NIP dan kode pribadi dari Admin.';
        $valid = $code && $code->used_at && ! $code->revoked_at
            && $code->participant?->user_id === $request->user()->id
            && hash_equals($code->version, (string) $request->session()->get('assessment_access_version'));
        // An existing session may still present its submitted PDF during the
        // program window. New logins after all submissions remain prohibited.
        if ($valid && ($denial = app(AssessmentAccessService::class)->denial($code->participant, checkCompleted: false))) {
            $valid = false;
            $message = $denial;
        }
        if (! $valid) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => $message], 401, ['Cache-Control' => 'no-store'])
                : to_route('login')->with('error', $message);
        }
        $request->attributes->set('assessment_access', $code);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
