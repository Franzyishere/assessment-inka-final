<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationMaterialPage;
use App\Models\SimulationSession;
use App\Models\SimulationSessionEvent;
use App\Models\SimulationSubmission;
use App\Models\SimulationType;
use App\Support\RichTextSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssessmentSimulationController extends Controller
{
    public function index(Request $request): View
    {
        $participations = AssessmentParticipant::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'assigned')
            ->whereHas('program', fn ($query) => $query
                ->where('status', 'active')
                ->where(fn ($timeQuery) => $timeQuery->whereNull('ends_at')->orWhere('ends_at', '>', now())))
            ->with(['program.simulations' => fn ($query) => $query->orderBy('id'), 'program.simulations.scenario.type', 'sessions'])
            ->latest('assigned_at')
            ->get();

        $participations->each(function ($participation): void {
            $participation->program->setRelation('simulations', $participation->program->simulations
                ->filter(function ($simulation) use ($participation): bool {
                    if (($simulation->closes_at && $simulation->closes_at->isPast())
                        || ($participation->program->ends_at && $participation->program->ends_at->isPast())) {
                        return false;
                    }
                    if ($simulation->scenario->type->delivery_mode !== 'case_response') {
                        return $participation->sessions->firstWhere('assessment_program_simulation_id', $simulation->id)?->status !== 'submitted';
                    }

                    if ($participation->sessions->firstWhere('assessment_program_simulation_id', $simulation->id)?->status === 'submitted') {
                        return false;
                    }

                    if ($participation->requiresSimulationThreeChoice()) {
                        return $simulation->scenario->simulation_package === 'ci_3';
                    }

                    return $simulation->scenario->simulation_package === $participation->simulationThreePackageKey();
                })
                ->values());
        });

        $search = mb_strtolower(trim((string) $request->query('search')));
        if ($search) {
            $participations = $participations->map(function ($participation) use ($search) {
                if (! str_contains(mb_strtolower($participation->program->name), $search)) {
                    $participation->program->setRelation('simulations', $participation->program->simulations->filter(fn ($simulation) => str_contains(mb_strtolower($simulation->scenario->type->name), $search)
                        || str_contains(mb_strtolower($simulation->scenario->simulationThreePackageLabel() ?? ''), $search))->values());
                }

                return $participation;
            })->filter(fn ($participation) => str_contains(mb_strtolower($participation->program->name), $search) || $participation->program->simulations->isNotEmpty())->values();
        }

        return view('pages.participant.simulations.index', [
            'title' => 'Simulasi Saya',
            'participations' => $participations,
        ]);
    }

    public function show(Request $request, AssessmentProgramSimulation $programSimulation): View
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->session($programSimulation, $participation);

        return view('pages.participant.simulations.show', compact('programSimulation', 'participation', 'session') + [
            'title' => $programSimulation->scenario->type->name,
        ]);
    }

    public function start(Request $request, AssessmentProgramSimulation $programSimulation): RedirectResponse
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        if ($programSimulation->scenario->type->delivery_mode === 'case_response') {
            abort_if($participation->requiresSimulationThreeChoice(), 422, 'Paket Simulasi 3 belum ditetapkan oleh asesor.');
            abort_unless($programSimulation->scenario->simulation_package === $participation->simulationThreePackageKey(), 404);
        }
        abort_unless($this->isAvailable($programSimulation), 403, 'Simulasi belum tersedia atau sudah ditutup.');
        abort_unless(in_array($programSimulation->scenario->type->delivery_mode, ['multi_page_response', 'file_upload', 'case_response', 'assessor_observation'], true), 403, 'Flow simulasi ini belum tersedia.');

        if ($programSimulation->scenario->type->delivery_mode === 'assessor_observation') {
            $problemAnalysisCompleted = SimulationSession::query()
                ->where('assessment_participant_id', $participation->id)
                ->where('status', 'submitted')
                ->whereHas('programSimulation', fn ($query) => $query->where('assessment_program_id', $programSimulation->assessment_program_id))
                ->whereHas('programSimulation.scenario.type', fn ($query) => $query->where('code', SimulationType::PROBLEM_ANALYSIS))
                ->exists();
            abort_unless($problemAnalysisCompleted, 422, 'Simulasi 1 harus dikumpulkan sebelum Simulasi 2 dapat dimulai.');
        }

        $session = $this->session($programSimulation, $participation);
        abort_if($session?->status === 'submitted', 403, 'Simulasi sudah dikumpulkan.');

        $session ??= new SimulationSession([
            'assessment_program_simulation_id' => $programSimulation->id,
            'assessment_participant_id' => $participation->id,
        ]);
        if (! $session->started_at) {
            $session->fill([
                'status' => 'in_progress',
                'started_at' => now(),
                'expires_at' => now()->addMinutes($programSimulation->scenario->duration_minutes ?? 60),
                'session_token' => hash('sha256', Str::uuid()->toString()),
                'device_identifier' => hash('sha256', $request->userAgent().'|'.$request->ip()),
                'last_ip_address' => $request->ip(),
                'last_user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            ])->save();
        }

        return match ($programSimulation->scenario->type->delivery_mode) {
            'file_upload' => redirect()->route('peserta-assessment.simulations.presentation', $programSimulation),
            'assessor_observation' => redirect()->route('peserta-assessment.simulations.lgd-review', $programSimulation),
            'case_response' => $programSimulation->scenario->materialPages->isNotEmpty()
                ? redirect()->route('peserta-assessment.simulations.material', [$programSimulation, 1])
                : redirect()->route('peserta-assessment.simulations.case-response', $programSimulation),
            default => redirect()->route('peserta-assessment.simulations.material', [$programSimulation, 1]),
        };
    }

    public function caseResponse(Request $request, AssessmentProgramSimulation $programSimulation): View
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        abort_unless($programSimulation->scenario->type->delivery_mode === 'case_response', 404);

        return view('pages.participant.simulations.case-response', ['title' => $programSimulation->scenario->type->name, 'programSimulation' => $programSimulation, 'session' => $session]);
    }

    public function submitCaseResponse(Request $request, AssessmentProgramSimulation $programSimulation): RedirectResponse
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        abort_unless($programSimulation->scenario->type->delivery_mode === 'case_response', 404);
        $validated = $request->validate(['response' => ['required', 'string', 'max:50000']]);

        SimulationSubmission::create([
            'simulation_session_id' => $session->id,
            'response_text' => $validated['response'],
            'revision' => 1,
            'submitted_at' => now(),
        ]);
        $session->update(['status' => 'submitted', 'submitted_at' => now()]);

        return to_route('peserta-assessment.simulations.index')->with('success', 'Respons kasus berhasil dikumpulkan.');
    }

    public function presentation(Request $request, AssessmentProgramSimulation $programSimulation): View
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        abort_unless($programSimulation->scenario->type->delivery_mode === 'file_upload', 404);

        return view('pages.participant.simulations.presentation', compact('programSimulation', 'session') + [
            'title' => 'Upload Presentasi',
        ]);
    }

    public function submitPresentation(Request $request, AssessmentProgramSimulation $programSimulation): RedirectResponse
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        abort_unless($programSimulation->scenario->type->delivery_mode === 'file_upload', 404);
        $validated = $request->validate([
            'presentation' => ['required', 'file', 'max:25600', 'mimes:pdf'],
        ], [
            'presentation.mimes' => 'File presentasi harus berformat PDF.',
            'presentation.max' => 'Ukuran file maksimal 25 MB.',
        ]);

        $file = $validated['presentation'];
        $storedName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('simulation-submissions/'.$session->id, $storedName, 'local');
        abort_unless($path, 500, 'File gagal disimpan.');

        SimulationSubmission::create([
            'simulation_session_id' => $session->id,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_checksum' => hash_file('sha256', Storage::disk('local')->path($path)),
            'revision' => 1,
            'submitted_at' => now(),
        ]);
        $session->update(['status' => 'submitted', 'submitted_at' => now()]);

        return to_route('peserta-assessment.simulations.index')->with('success', 'File presentasi berhasil dikumpulkan.');
    }

    public function material(Request $request, AssessmentProgramSimulation $programSimulation, int $page): View
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        abort_unless(in_array($programSimulation->scenario->type->delivery_mode, ['multi_page_response', 'case_response'], true), 404);

        $pages = $programSimulation->scenario->materialPages;
        abort_unless($page >= 1 && $page <= $pages->count(), 404);
        $submission = $session->submissions()->where('revision', 1)->first();
        $responses = json_decode($submission?->response_text ?? '{}', true) ?: [];

        return view('pages.participant.simulations.material', [
            'title' => $programSimulation->scenario->type->name,
            'programSimulation' => $programSimulation,
            'session' => $session,
            'materials' => $pages->values(),
            'responses' => $responses,
            'material' => $pages->values()->get($page - 1),
            'pageNumber' => $page,
            'pageCount' => $pages->count(),
            'response' => $responses[$page] ?? '',
        ]);
    }

    public function saveMaterial(Request $request, AssessmentProgramSimulation $programSimulation, int $page): RedirectResponse|JsonResponse
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        $pages = $programSimulation->scenario->materialPages;
        abort_unless(in_array($programSimulation->scenario->type->delivery_mode, ['multi_page_response', 'case_response'], true) && $page >= 1 && $page <= $pages->count(), 404);

        $material = $pages->values()->get($page - 1);
        $validated = $request->validate(['response' => [$material->is_required ? 'required' : 'nullable', 'string', 'max:100000']]);
        $sanitizedResponse = RichTextSanitizer::sanitize($validated['response'] ?? '');
        if ($material->is_required && blank(trim(html_entity_decode(strip_tags($sanitizedResponse))))) {
            return back()->withInput()->withErrors(['response' => 'Jawaban wajib diisi sebelum disimpan.']);
        }
        $submission = $session->submissions()->firstOrNew(['revision' => 1]);
        $responses = json_decode($submission->response_text ?? '{}', true) ?: [];
        $responses[$page] = $sanitizedResponse;
        $submission->response_text = json_encode($responses, JSON_UNESCAPED_UNICODE);
        $submission->save();

        if ($request->boolean('submit_after_save')) {
            abort_unless($page === $pages->count(), 422, 'Aksi simpan dan kumpulkan hanya tersedia pada materi terakhir.');

            $missingPage = $pages->values()->first(
                fn ($pageMaterial, $index) => $pageMaterial->is_required
                    && blank(trim(html_entity_decode(strip_tags($responses[$index + 1] ?? ''))))
            );

            if ($missingPage) {
                $missingPageNumber = $pages->values()->search(
                    fn ($pageMaterial) => $pageMaterial->is($missingPage)
                ) + 1;

                return redirect()
                    ->route('peserta-assessment.simulations.material', [$programSimulation, $missingPageNumber])
                    ->withErrors(['response' => 'Materi wajib ini harus dijawab sebelum simulasi dikumpulkan.']);
            }

            $session->update(['status' => 'submitted', 'submitted_at' => now()]);
            $submission->update(['submitted_at' => now()]);

            return to_route('peserta-assessment.simulations.index')
                ->with('success', 'Jawaban berhasil disimpan dan simulasi telah dikumpulkan. Jawaban tidak dapat diubah kembali.');
        }

        $nextPage = min($page + 1, $pages->count());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Jawaban tersimpan.',
                'next_page' => $nextPage,
                'next_url' => route('peserta-assessment.simulations.material', [$programSimulation, $nextPage]),
            ]);
        }

        return redirect()->route('peserta-assessment.simulations.material', [$programSimulation, $nextPage])->with('success', 'Jawaban tersimpan.');
    }

    public function submit(Request $request, AssessmentProgramSimulation $programSimulation): RedirectResponse
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        $submission = $session->submissions()->where('revision', 1)->first();
        abort_unless($submission, 422, 'Belum ada jawaban yang tersimpan.');

        if ($programSimulation->scenario->type->delivery_mode === 'multi_page_response') {
            $responses = json_decode($submission->response_text ?? '{}', true) ?: [];
            $missingPage = $programSimulation->scenario->materialPages
                ->first(fn ($material, $index) => $material->is_required
                    && blank($responses[$index + 1] ?? null));

            if ($missingPage) {
                $pageNumber = $programSimulation->scenario->materialPages->search(
                    fn ($material) => $material->is($missingPage)
                ) + 1;

                return redirect()
                    ->route('peserta-assessment.simulations.material', [$programSimulation, $pageNumber])
                    ->withErrors(['response' => 'Halaman wajib ini harus dijawab sebelum simulasi dikumpulkan.']);
            }
        }

        $session->update(['status' => 'submitted', 'submitted_at' => now()]);
        $submission->update(['submitted_at' => now()]);

        return to_route('peserta-assessment.simulations.index')->with('success', 'Simulasi berhasil dikumpulkan.');
    }

    public function materialPdf(Request $request, AssessmentProgramSimulation $programSimulation, SimulationMaterialPage $material)
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        abort_unless($material->simulation_scenario_id === $programSimulation->simulation_scenario_id, 404);
        abort_unless($material->attachment_path && Storage::disk('local')->exists($material->attachment_path), 404);

        return Storage::disk('local')->response(
            $material->attachment_path,
            'materi-assessment.pdf',
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="materi-assessment.pdf"',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function lgdReview(Request $request, AssessmentProgramSimulation $programSimulation): View
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        abort_unless($programSimulation->scenario->type->delivery_mode === 'assessor_observation', 404);

        $sourceSimulation = AssessmentProgramSimulation::query()
            ->where('assessment_program_id', $programSimulation->assessment_program_id)
            ->whereHas('scenario.type', fn ($query) => $query->where('code', SimulationType::PROBLEM_ANALYSIS))
            ->with(['scenario.type', 'scenario.materialPages'])
            ->firstOrFail();
        $sourceSession = $this->session($sourceSimulation, $participation);
        abort_unless($sourceSession?->status === 'submitted', 403, 'Simulasi 1 harus dikumpulkan sebelum materi LGD dapat direview.');
        $responses = json_decode($sourceSession->submissions()->where('revision', 1)->value('response_text') ?? '{}', true) ?: [];

        return view('pages.participant.simulations.lgd-review', compact('programSimulation', 'sourceSimulation', 'responses', 'session') + [
            'title' => 'Leaderless Group Discussion',
        ]);
    }

    public function submitLgd(Request $request, AssessmentProgramSimulation $programSimulation): RedirectResponse
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        abort_unless($programSimulation->scenario->type->delivery_mode === 'assessor_observation', 404);
        $request->validate(['confirmation' => ['accepted']], [
            'confirmation.accepted' => 'Konfirmasi penyelesaian Simulasi 2 wajib disetujui.',
        ]);
        $session->update(['status' => 'submitted', 'submitted_at' => now()]);

        return to_route('peserta-assessment.simulations.index')
            ->with('success', 'Simulasi 2 berhasil disimpan dan dikumpulkan.');
    }

    public function recordEvent(Request $request, AssessmentProgramSimulation $programSimulation)
    {
        [$participation, $programSimulation] = $this->resolveAssignment($request, $programSimulation);
        $session = $this->activeSession($programSimulation, $participation);
        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:'.implode(',', SimulationSessionEvent::TYPES)],
            'client_time' => ['nullable', 'date'],
            'page_url' => ['nullable', 'string', 'max:1000'],
            'visibility_state' => ['nullable', 'string', 'in:visible,hidden,prerender'],
            'is_fullscreen' => ['nullable', 'boolean'],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        if ($session->events()->where('event_type', $validated['event_type'])->where('occurred_at', '>=', now()->subSeconds(2))->exists()) {
            return response()->json(['recorded' => false, 'message' => 'Aktivitas duplikat diabaikan.'], 202);
        }

        $session->events()->create([
            'event_type' => $validated['event_type'],
            'metadata' => [
                'client_time' => $validated['client_time'] ?? null,
                'page_url' => $validated['page_url'] ?? null,
                'visibility_state' => $validated['visibility_state'] ?? null,
                'is_fullscreen' => $validated['is_fullscreen'] ?? null,
                'sequence' => $validated['sequence'] ?? null,
            ],
            'ip_address' => $request->ip(),
            'occurred_at' => now(),
        ]);
        $session->update(['last_ip_address' => $request->ip(), 'last_user_agent' => Str::limit((string) $request->userAgent(), 1000, '')]);

        return response()->json(['recorded' => true, 'violation_count' => $session->events()->count()], 201);
    }

    private function resolveAssignment(Request $request, AssessmentProgramSimulation $programSimulation): array
    {
        $programSimulation->load(['program', 'scenario.type', 'scenario.materialPages']);
        $participation = AssessmentParticipant::where('assessment_program_id', $programSimulation->assessment_program_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'assigned')
            ->firstOrFail();
        if ($programSimulation->scenario->type->delivery_mode === 'case_response') {
            abort_unless($participation->isEligibleForSimulationThreePackage($programSimulation->scenario->simulation_package), 404);
        }

        return [$participation, $programSimulation];
    }

    private function session(AssessmentProgramSimulation $programSimulation, AssessmentParticipant $participation): ?SimulationSession
    {
        return SimulationSession::where('assessment_program_simulation_id', $programSimulation->id)
            ->where('assessment_participant_id', $participation->id)->first();
    }

    private function activeSession(AssessmentProgramSimulation $programSimulation, AssessmentParticipant $participation): SimulationSession
    {
        $session = $this->session($programSimulation, $participation);
        abort_unless($session && $session->status === 'in_progress', 403, 'Sesi simulasi tidak aktif.');
        if ($session->expires_at?->isPast()) {
            $session->update(['status' => 'expired']);
            abort(403, 'Waktu pengerjaan simulasi telah habis.');
        }

        return $session;
    }

    private function isAvailable(AssessmentProgramSimulation $programSimulation): bool
    {
        return $programSimulation->program->status === 'active'
            && in_array($programSimulation->scenario->status, ['active', 'published'], true)
            && $programSimulation->scenario->type->is_active
            && $programSimulation->status === 'scheduled'
            && (! $programSimulation->opens_at || $programSimulation->opens_at->isPast())
            && (! $programSimulation->closes_at || $programSimulation->closes_at->isFuture());
    }
}
