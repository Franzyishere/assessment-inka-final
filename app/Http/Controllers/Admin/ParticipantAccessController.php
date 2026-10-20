<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Services\AssessmentAccessService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParticipantAccessController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $programs = AssessmentProgram::where(fn ($query) => $query->whereNull('archived_at')->orWhereIn('status', ['draft', 'active']))
            ->when($search !== '', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']))
            ->withCount('participants')->latest()->paginate(15)->withQueryString();

        return response()->view('pages.admin.participant-access.index', compact('programs') + ['title' => 'Akses Peserta'])
            ->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, AssessmentProgram $assessmentProgram)
    {
        $search = mb_strtolower(trim((string) $request->query('search')));
        $participants = $assessmentProgram->participants()->with(['user', 'accessCode'])
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where(fn ($w) => $w
                ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])->orWhere('employee_number', 'like', "%{$search}%"))))
            ->orderBy('id')->paginate(20)->withQueryString();

        return response()->view('pages.admin.participant-access.show', [
            'title' => 'Akses Peserta', 'program' => $assessmentProgram, 'participants' => $participants,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function manage(Request $request, AssessmentProgram $assessmentProgram, AssessmentParticipant $participant, AssessmentAccessService $service)
    {
        abort_unless($participant->assessment_program_id === $assessmentProgram->id, 404);
        $action = $request->validate(['action' => ['required', 'in:reveal,issue,replace,revoke']])['action'];

        return DB::transaction(function () use ($request, $assessmentProgram, $participant, $service, $action) {
            AssessmentProgram::whereKey($assessmentProgram->id)->lockForUpdate()->firstOrFail();
            $participant = AssessmentParticipant::lockForUpdate()->findOrFail($participant->id);
            $code = $participant->accessCode()->lockForUpdate()->first();
            if ($action === 'revoke') {
                abort_unless($code, 404);
                $code->update(['revoked_at' => now()]);
            } elseif ($action === 'reveal') {
                abort_unless($code && ! $code->used_at && ! $code->revoked_at, 422, 'Kode tidak tersedia. Terbitkan kode pengganti bila diperlukan.');
            } else {
                $code = $service->issue($participant, replace: $action === 'replace');
            }
            AuditLogger::record($request, 'participant.access.'.$action, $participant, ['program_id' => $assessmentProgram->id]);
            if ($action === 'reveal') {
                return response()->view('pages.admin.participant-access.code', [
                    'title' => 'Kode Akses Peserta', 'participant' => $participant, 'program' => $assessmentProgram, 'code' => $code,
                ])->header('Cache-Control', 'no-store, private');
            }

            return back()->with('success', $action === 'revoke'
                ? 'Akses dicabut. Jawaban peserta tetap disimpan.'
                : 'Kode akses tersedia. Kode pengganti membatalkan sesi login sebelumnya.');
        });
    }
}
