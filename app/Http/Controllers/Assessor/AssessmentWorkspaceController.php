<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Models\AssessorAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentWorkspaceController extends Controller
{
    public function participants(Request $request): View
    {
        $assignments = $this->assignments($request);
        $programs = $assignments
            ->groupBy(fn ($assignment) => $assignment->programSimulation->assessment_program_id)
            ->map(function ($programAssignments) {
                $program = $programAssignments->first()->programSimulation->program;
                $assignedSimulations = $programAssignments->pluck('programSimulation');

                $program->participants->each(function ($participant) use ($assignedSimulations): void {
                    $simulationIds = $assignedSimulations->filter(fn ($simulation) => $simulation->scenario->type->delivery_mode !== 'case_response'
                        || $simulation->scenario->simulation_package === $participant->simulationThreePackageKey())->pluck('id');
                    $scopedSessions = $participant->sessions->whereIn('assessment_program_simulation_id', $simulationIds);
                    $participant->setAttribute('assigned_simulations_count', $simulationIds->count());
                    $participant->setAttribute('started_sessions_count', $scopedSessions->count());
                    $participant->setAttribute('submitted_sessions_count', $scopedSessions->where('status', 'submitted')->count());
                });

                return $program;
            })->values();

        return view('pages.assessor.participants.index', [
            'title' => 'Peserta Assessment',
            'programs' => $programs,
        ]);
    }

    public function monitoring(Request $request): View
    {
        $assignments = $this->assignments($request);

        $simulations = $assignments->map(function ($assignment) use ($request) {
            $simulation = $assignment->programSimulation;
            $sessions = $simulation->sessions;
            $submitted = $sessions->where('status', 'submitted');
            $reviewed = $submitted->filter(fn ($session) => $session->reviews
                ->where('assessor_id', $request->user()->id)
                ->contains('status', 'submitted'));
            $eligibleParticipants = $simulation->program->participants->where('status', 'assigned');
            if ($simulation->scenario->type->delivery_mode === 'case_response') {
                $package = $simulation->scenario->simulation_package;
                $eligibleParticipants = $eligibleParticipants->filter(fn ($participant) => $participant->simulationThreePackageKey() === $package);
            }
            $expected = $eligibleParticipants->count();

            $simulation->setAttribute('expected_count', $expected);
            $simulation->setAttribute('started_count', $sessions->count());
            $simulation->setAttribute('submitted_count', $submitted->count());
            $simulation->setAttribute('reviewed_count', $reviewed->count());
            $simulation->setAttribute('event_count', $sessions->sum(fn ($session) => $session->events->count()));
            $simulation->setAttribute('progress', $expected > 0 ? round(($submitted->count() / $expected) * 100) : 0);

            return $simulation;
        });

        return view('pages.assessor.monitoring.index', [
            'title' => 'Monitoring Simulasi',
            'simulations' => $simulations,
        ]);
    }

    private function assignments(Request $request)
    {
        return AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->with([
                'programSimulation.program.participants.user',
                'programSimulation.program.participants.sessions.reviews',
                'programSimulation.scenario.type',
                'programSimulation.sessions.events',
                'programSimulation.sessions.reviews',
            ])
            ->latest('assigned_at')
            ->get();
    }
}
