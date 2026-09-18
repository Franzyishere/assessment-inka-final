<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentInvitation;
use App\Models\AssessmentProgram;
use App\Services\AssessmentInvitationService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssessmentInvitationController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_strtolower(trim((string) $request->query('search')));
        $programs = AssessmentProgram::query()
            ->when($search !== '', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%']))
            ->withCount(['participants', 'participants as invited_count' => fn ($q) => $q->whereHas('invitation')])
            ->latest('id')->paginate(15)->withQueryString();

        return view('pages.admin.invitations.index', compact('programs') + ['title' => 'Undangan Assessment']);
    }

    public function show(Request $request, AssessmentProgram $assessmentProgram)
    {
        $search = mb_strtolower(trim((string) $request->query('search')));
        $participants = $assessmentProgram->participants()
            ->with(['user', 'program', 'invitation.latestInvitationDelivery', 'invitation.latestOtpDelivery'])
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($user) => $user->where(fn ($nested) => $nested
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])->orWhereRaw('LOWER(email) LIKE ?', ['%'.$search.'%']))))
            ->orderBy('id')->paginate(25)->withQueryString();
        // Supply the already-loaded relations for derived access labels (no per-row queries).
        foreach ($participants as $participant) {
            $participant->invitation?->setRelation('participant', $participant);
        }

        return view('pages.admin.invitations.show', ['program' => $assessmentProgram, 'participants' => $participants, 'title' => 'Undangan Assessment']);
    }

    public function send(Request $request, AssessmentProgram $assessmentProgram, AssessmentInvitationService $service)
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['required', 'integer', 'distinct']]);
        $participants = $assessmentProgram->participants()->whereIn('id', $data['ids'])->get();
        abort_unless($participants->count() === count($data['ids']), 422, 'Peserta tidak sesuai program.');
        $service->mailer();
        $sent = 0;
        $failed = 0;
        foreach ($participants as $participant) {
            try {
                $invitation = $service->issue($participant, $request->user()->id);
                AuditLogger::record($request, 'invitation.issued', $invitation);
                $sent++;
            } catch (ValidationException $exception) {
                $failed++;
            }
        }

        return back()->with($failed ? 'error' : 'success', "{$sent} undangan diproses; {$failed} tidak dapat diterbitkan. Periksa status email, tanggal program, dan status peserta. Pengiriman ulang membatalkan tautan serta sesi login sebelumnya.");
    }

    public function revoke(Request $request, AssessmentProgram $assessmentProgram, AssessmentInvitation $invitation, AssessmentInvitationService $service)
    {
        abort_unless($invitation->participant->assessment_program_id === $assessmentProgram->id, 404);
        $service->revoke($invitation);
        AuditLogger::record($request, 'invitation.revoked', $invitation);

        return back()->with('success', 'Undangan dicabut. Akses login peserta dihentikan; jawaban tetap tersimpan.');
    }
}
