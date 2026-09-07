<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessor\UpdateSimulationReviewRequest;
use App\Models\AssessmentProgram;
use App\Models\AssessorAssignment;
use App\Models\SimulationReview;
use App\Models\SimulationSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulationReviewController extends Controller
{
    public function index(Request $request): View
    {
        $assessorId = $request->user()->id;
        $search = mb_strtolower(trim((string) $request->query('search')));
        $programs = AssessmentProgram::query()
            ->whereHas('simulations.assessorAssignments', fn ($query) => $query->where('assessor_id', $assessorId))
            ->with(['simulations' => fn ($query) => $query
                ->whereHas('assessorAssignments', fn ($assignmentQuery) => $assignmentQuery->where('assessor_id', $assessorId))
                ->with(['sessions' => fn ($sessionQuery) => $sessionQuery
                    ->where('status', 'submitted')
                    ->with(['participant.user', 'reviews' => fn ($reviewQuery) => $reviewQuery->where('assessor_id', $assessorId)])])])
            ->when($search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
            ->orderByDesc('starts_at')
            ->orderBy('name')
            ->paginate(9)->withQueryString();

        return view('pages.assessor.reviews.index', ['title' => 'Penilaian & Rekomendasi', 'programs' => $programs]);
    }

    public function program(Request $request, AssessmentProgram $program): View
    {
        $assessorId = $request->user()->id;
        $search = mb_strtolower(trim((string) $request->query('search')));
        $assignmentIds = AssessorAssignment::query()
            ->where('assessor_id', $assessorId)
            ->whereHas('programSimulation', fn ($query) => $query->where('assessment_program_id', $program->id))
            ->pluck('assessment_program_simulation_id');

        abort_if($assignmentIds->isEmpty(), 403);

        $sessions = SimulationSession::query()
            ->whereIn('assessment_program_simulation_id', $assignmentIds)
            ->where('status', 'submitted')
            ->when($search, fn ($query) => $query->whereHas('participant.user', fn ($userQuery) => $userQuery
                ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])))
            ->with(['programSimulation.scenario.type', 'participant.user', 'reviews' => fn ($query) => $query->where('assessor_id', $assessorId)])
            ->get()
            ->sortBy(fn ($session) => mb_strtolower($session->participant->user->name).'|'.str_pad((string) ($session->programSimulation->scenario->type->sequence ?? 0), 3, '0', STR_PAD_LEFT))
            ->values();

        return view('pages.assessor.reviews.program', [
            'title' => 'Peserta Penilaian',
            'program' => $program,
            'participantSessions' => $sessions->groupBy('assessment_participant_id'),
        ]);
    }

    public function edit(Request $request, SimulationSession $session): View
    {
        $this->authorizeSession($request, $session);
        abort_unless($session->status === 'submitted', 403, 'Peserta belum mengumpulkan simulasi.');
        $session->load(['programSimulation.program', 'programSimulation.scenario.type', 'programSimulation.scenario.materialPages', 'participant.user', 'submissions', 'events']);
        $review = $session->reviews()->where('assessor_id', $request->user()->id)->first();
        $submission = $session->submissions->sortByDesc('revision')->first();
        $responses = $submission && ! $submission->storage_path ? (json_decode($submission->response_text ?? '{}', true) ?: []) : [];

        return view('pages.assessor.reviews.edit', compact('session', 'review', 'submission', 'responses') + ['title' => 'Penilaian Simulasi']);
    }

    public function update(UpdateSimulationReviewRequest $request, SimulationSession $session): RedirectResponse
    {
        $this->authorizeSession($request, $session);
        abort_unless($session->status === 'submitted', 403, 'Peserta belum mengumpulkan simulasi.');
        $existingReview = $session->reviews()->where('assessor_id', $request->user()->id)->first();
        abort_if($existingReview?->status === 'submitted', 403, 'Penilaian sudah difinalisasi.');
        $data = $request->validated();

        SimulationReview::updateOrCreate(
            ['simulation_session_id' => $session->id, 'assessor_id' => $request->user()->id],
            [...$data, 'reviewed_at' => $data['status'] === 'submitted' ? now() : null]
        );

        $message = $data['status'] === 'submitted' ? 'Penilaian berhasil difinalisasi.' : 'Draft penilaian berhasil disimpan.';

        return to_route('asesor.reviews.program', $session->programSimulation->assessment_program_id)->with('success', $message);
    }

    private function authorizeSession(Request $request, SimulationSession $session): void
    {
        abort_unless(AssessorAssignment::where('assessment_program_simulation_id', $session->assessment_program_simulation_id)
            ->where('assessor_id', $request->user()->id)->exists(), 403);
    }
}
