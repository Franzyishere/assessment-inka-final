<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AssessmentInvitation;
use App\Services\AssessmentInvitationService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssessmentInvitationController extends Controller
{
    private function invitation(string $token): AssessmentInvitation
    {
        return AssessmentInvitation::with('participant.program', 'participant.user')->where('token_hash', hash('sha256', $token))->firstOrFail();
    }

    public function show(Request $request, string $token)
    {
        $invitation = $this->invitation($token);

        return response()->view('pages.auth.invitation', compact('invitation', 'token'))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function requestOtp(Request $request, string $token, AssessmentInvitationService $service)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $secret = $request->session()->get('assessment_otp_browser', Str::random(64));
        $request->session()->put('assessment_otp_browser', $secret);
        $service->sendOtp($this->invitation($token), $data['email'], $secret);
        $request->session()->put('assessment_otp_requested', hash('sha256', $token));

        return back()->with('success', 'Jika email sesuai undangan, OTP telah dikirim. Periksa kotak masuk dan spam.');
    }

    public function verify(Request $request, string $token, AssessmentInvitationService $service)
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $invitation = $service->verify($this->invitation($token), $data['otp'], (string) $request->session()->get('assessment_otp_browser'));
        if (! $invitation) {
            throw ValidationException::withMessages(['otp' => 'OTP tidak valid, sudah digunakan, atau kedaluwarsa. Maksimal 5 percobaan per kode.']);
        }
        Auth::login($invitation->participant->user, false);
        $request->session()->regenerate();
        $request->session()->forget(['assessment_otp_browser', 'assessment_otp_requested', 'url.intended']);
        $request->session()->put('assessment_invitation_id', $invitation->id);
        $request->session()->put('assessment_invitation_version', $invitation->token_hash);
        AuditLogger::record($request, 'auth.otp-login', $invitation);

        return redirect()->route('peserta-assessment.simulations.index');
    }
}
