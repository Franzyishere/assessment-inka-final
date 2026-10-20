<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AssessmentAccessService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipantAccessController extends Controller
{
    public function __invoke(Request $request, AssessmentAccessService $service)
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:100'],
            'access_code' => ['required', 'string', 'size:12', 'regex:/^[a-fA-F0-9]{12}$/'],
        ], ['access_code.regex' => 'Format kode akses tidak valid.', 'access_code.size' => 'Kode akses terdiri dari 12 karakter.']);
        $code = $service->consume($data['nip'], $data['access_code']);
        $request->session()->invalidate();
        Auth::guard('web')->login($code->participant->user);
        $request->session()->regenerate();
        $request->session()->put(['assessment_access_id' => $code->id, 'assessment_access_version' => $code->version]);
        AuditLogger::record($request, 'participant.access.login', $code->participant);

        return to_route('peserta-assessment.simulations.index');
    }
}
