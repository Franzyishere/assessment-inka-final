<?php

namespace App\Http\Middleware;

use App\Models\AssessmentInvitation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAssessmentInvitation
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role !== 'peserta_assessment' || $request->routeIs('logout')) {
            return $next($request);
        }
        $invitation = AssessmentInvitation::with('participant.user', 'participant.program')->find($request->session()->get('assessment_invitation_id'));
        if (! $invitation || ! $invitation->isAccessible()
            || $invitation->participant->user_id !== $request->user()->id
            || ! hash_equals($invitation->token_hash, (string) $request->session()->get('assessment_invitation_version'))) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $message = 'Akses undangan tidak aktif. Silakan masuk melalui undangan yang berlaku pada hari pelaksanaan.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 401)
                : redirect()->route('login')->with('error', $message);
        }
        $request->attributes->set('assessment_invitation', $invitation);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
