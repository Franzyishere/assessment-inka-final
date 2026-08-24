<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationMaterialPage;
use App\Models\SimulationSession;
use App\Models\SimulationSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssignedSimulationController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->with(['programSimulation.program.participants', 'programSimulation.scenario.type', 'programSimulation.sessions'])
            ->orderBy('assigned_at')->orderBy('id')->get()
            ->reject(function ($assignment): bool {
                $simulation = $assignment->programSimulation;
                $eligible = $simulation->program->participants->where('status', 'assigned');
                if ($simulation->scenario->type->delivery_mode === 'case_response') {
                    $eligible = $eligible->filter(fn ($participant) => $participant->simulationThreePackageKey() === $simulation->scenario->simulation_package);
                }
                if ($eligible->isEmpty()) return false;

                $submittedParticipantIds = $simulation->sessions->where('status', 'submitted')->pluck('assessment_participant_id')->unique();

                return $eligible->pluck('id')->diff($submittedParticipantIds)->isEmpty();
            })->values();

        return view('pages.assessor.simulations.index', ['title' => 'Simulasi Ditugaskan', 'assignments' => $assignments]);
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
