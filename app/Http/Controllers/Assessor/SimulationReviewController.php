<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessor\UpdateSimulationReviewRequest;
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
        $assignments = AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->pluck('assessment_program_simulation_id');
        $sessions = SimulationSession::query()
            ->whereIn('assessment_program_simulation_id', $assignments)
            ->where('status', 'submitted')
            ->with(['programSimulation.program', 'programSimulation.scenario.type', 'participant.user', 'reviews' => fn ($query) => $query->where('assessor_id', $request->user()->id)])
            ->orderByDesc('submitted_at')->orderBy('id')->paginate(12);

        return view('pages.assessor.reviews.index', ['title' => 'Penilaian & Rekomendasi', 'sessions' => $sessions]);
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

        return to_route('asesor.reviews.index')->with('success', $message);
    }

    private function authorizeSession(Request $request, SimulationSession $session): void
    {
        abort_unless(AssessorAssignment::where('assessment_program_simulation_id', $session->assessment_program_simulation_id)
            ->where('assessor_id', $request->user()->id)->exists(), 403);
    }
}
