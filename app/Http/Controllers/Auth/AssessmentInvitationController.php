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

    public function show(Request $request, string $token, AssessmentInvitationService $service)
    {
        $invitation = $this->invitation($token);
        $maskedEmail = $this->maskEmail($invitation->email);
        $otpSent = false;

        if ($invitation->isAccessible()) {
            $secret = $request->session()->get('assessment_otp_browser', Str::random(64));
            $request->session()->put('assessment_otp_browser', $secret);

            $tokenHash = hash('sha256', $token);
            if ($request->session()->get('assessment_otp_requested') !== $tokenHash) {
                try {
                    $service->sendOtp($invitation, $invitation->email, $secret);
                    $request->session()->put('assessment_otp_requested', $tokenHash);
                    $otpSent = true;
                } catch (ValidationException $e) {
                    // Cooldown or recent request still active
                    $request->session()->put('assessment_otp_requested', $tokenHash);
                } catch (\Throwable $e) {
                    session()->flash('error', 'Gagal mengirim kode OTP otomatis. Silakan gunakan tombol kirim ulang di bawah.');
                }
            } else {
                $otpSent = true;
            }
        }

        return response()->view('pages.auth.invitation', compact('invitation', 'token', 'maskedEmail', 'otpSent'))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function requestOtp(Request $request, string $token, AssessmentInvitationService $service)
    {
        $invitation = $this->invitation($token);
        $email = $request->filled('email')
            ? $request->validate(['email' => ['required', 'email', 'max:255']])['email']
            : $invitation->email;

        $secret = $request->session()->get('assessment_otp_browser', Str::random(64));
        $request->session()->put('assessment_otp_browser', $secret);
        $service->sendOtp($invitation, $email, $secret);
        $request->session()->put('assessment_otp_requested', hash('sha256', $token));

        return back()->with('success', 'Kode OTP baru telah dikirim ke email Anda. Periksa kotak masuk dan spam.');
    }

    public function verify(Request $request, string $token, AssessmentInvitationService $service)
    {
        $request->merge([
            'otp' => strtoupper(trim((string) $request->input('otp'))),
        ]);
        $data = $request->validate([
            'otp' => ['required', 'string', 'size:8', 'alpha_num'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus berupa 8 karakter.',
            'otp.alpha_num' => 'Kode OTP hanya boleh berisi huruf dan angka.',
        ]);
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

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', max(1, $len - 2)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}
