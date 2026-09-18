<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationMaterialPage;
use App\Models\SimulationSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssignedSimulationController extends Controller
{
    public function index(Request $request): View
    {
        $programs = $this->activeAssignments($request)
            ->groupBy(fn (AssessorAssignment $assignment) => $assignment->programSimulation->assessment_program_id)
            ->map(function (Collection $assignments): array {
                return [
                    'program' => $assignments->first()->programSimulation->program,
                    'assignments_count' => $assignments->count(),
                    'submission_count' => $assignments->sum(fn (AssessorAssignment $assignment) => $assignment->programSimulation->sessions->where('status', 'submitted')->count()),
                ];
            })
            ->sortByDesc(fn (array $item) => sprintf(
                '%020d-%020d',
                $item['program']->created_at?->timestamp ?? 0,
                $item['program']->id
            ))
            ->when($request->filled('search'), fn (Collection $items) => $items->filter(fn (array $item) => str_contains(
                mb_strtolower($item['program']->name),
                mb_strtolower(trim((string) $request->query('search')))
            )))
            ->values();

        return view('pages.assessor.simulations.index', ['title' => 'Simulasi Ditugaskan', 'programs' => $programs]);
    }

    public function program(Request $request, AssessmentProgram $program): View
    {
        abort_unless(AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->whereHas('programSimulation', fn ($query) => $query->where('assessment_program_id', $program->id))
            ->exists(), 403);

        $assignments = $this->activeAssignments($request)
            ->filter(fn (AssessorAssignment $assignment) => $assignment->programSimulation->assessment_program_id === $program->id)
            ->sortBy(fn (AssessorAssignment $assignment) => $assignment->programSimulation->scenario->type->sequence ?? 999)
            ->values();

        return view('pages.assessor.simulations.program', compact('program', 'assignments') + ['title' => $program->name]);
    }

    private function activeAssignments(Request $request): Collection
    {
        return AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->with(['programSimulation.program.participants', 'programSimulation.scenario.type', 'programSimulation.sessions'])
            ->orderBy('assigned_at')->orderBy('id')->get()
            ->reject(function ($assignment): bool {
                $simulation = $assignment->programSimulation;
                $eligible = $simulation->program->participants->where('status', 'assigned');
                if ($simulation->scenario->type->delivery_mode === 'case_response') {
                    $eligible = $eligible->filter(fn ($participant) => $participant->simulationThreePackageKey() === $simulation->scenario->simulation_package);
                }
                if ($eligible->isEmpty()) {
                    return false;
                }

                $submittedParticipantIds = $simulation->sessions->where('status', 'submitted')->pluck('assessment_participant_id')->unique();

                return $eligible->pluck('id')->diff($submittedParticipantIds)->isEmpty();
            })->values();
    }

    public function show(Request $request, AssessmentProgramSimulation $programSimulation): View
    {
        $this->authorizeAssignment($request, $programSimulation);
        $programSimulation->load(['program.participants.user', 'scenario.type', 'sessions.participant.user', 'sessions.submissions']);
        if ($programSimulation->scenario->type->delivery_mode === 'case_response') {
            $package = $programSimulation->scenario->simulation_package;
            $programSimulation->program->setRelation('participants', $programSimulation->program->participants
                ->filter(fn ($participant) => $participant->simulationThreePackageKey() === $package)->values());
        }

        return view('pages.assessor.simulations.show', ['title' => $programSimulation->scenario->type->name, 'programSimulation' => $programSimulation]);
    }

    public function download(Request $request, SimulationSubmission $submission)
    {
        $submission->load('session.programSimulation');
        $this->authorizeAssignment($request, $submission->session->programSimulation);
        abort_unless($submission->storage_path && Storage::disk('local')->exists($submission->storage_path), 404);

        return Storage::disk('local')->download($submission->storage_path, $submission->original_filename);
    }

    public function preview(Request $request, SimulationSubmission $submission)
    {
        $submission->load('session.programSimulation');
        $this->authorizeAssignment($request, $submission->session->programSimulation);
        abort_unless($submission->mime_type === 'application/pdf' && $submission->storage_path && Storage::disk('local')->exists($submission->storage_path), 404);

        return Storage::disk('local')->response(
            $submission->storage_path,
            $submission->original_filename,
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']
        );
    }

    public function materialPreview(Request $request, AssessmentProgramSimulation $programSimulation, SimulationMaterialPage $material)
    {
        $this->authorizeAssignment($request, $programSimulation);
        abort_unless($material->simulation_scenario_id === $programSimulation->simulation_scenario_id, 404);
        abort_unless($material->attachment_path && Storage::disk('local')->exists($material->attachment_path), 404);

        return Storage::disk('local')->response($material->attachment_path, $material->attachment_name ?: 'materi.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }

    private function authorizeAssignment(Request $request, AssessmentProgramSimulation $programSimulation): void
    {
        abort_unless($programSimulation->assessorAssignments()->where('assessor_id', $request->user()->id)->exists(), 403);
    }
}
