<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessorAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentWorkspaceController extends Controller
{
    public function participants(Request $request): View
    {
        $search = mb_strtolower(trim((string) $request->query('search')));
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
            })
            ->map(function ($program) use ($search) {
                if (! $search || str_contains(mb_strtolower($program->name), $search)) {
                    return $program;
                }

                $program->setRelation('participants', $program->participants->filter(fn ($participant) => str_contains(mb_strtolower($participant->user->name), $search)
                    || str_contains(mb_strtolower($participant->user->email), $search))->values());

                return $program;
            })
            ->filter(fn ($program) => ! $search || str_contains(mb_strtolower($program->name), $search) || $program->participants->isNotEmpty())
            ->values();

        return view('pages.assessor.participants.index', [
            'title' => 'Peserta Assessment',
            'programs' => $programs,
        ]);
    }

    public function monitoring(Request $request): View
    {
        $search = mb_strtolower(trim((string) $request->query('search')));
        $programs = AssessmentProgram::query()
            ->whereHas('simulations.assessorAssignments', fn ($query) => $query->where('assessor_id', $request->user()->id))
            ->when($search !== '', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%']))
            ->withCount('participants')
            ->withCount(['simulations' => fn ($query) => $query->whereHas('assessorAssignments', fn ($q) => $q->where('assessor_id', $request->user()->id))])
            ->latest('created_at')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('pages.assessor.monitoring.index', ['title' => 'Monitoring Program Assessment', 'programs' => $programs]);
    }

    public function monitoringProgram(Request $request, AssessmentProgram $program): View
    {
        abort_unless($program->simulations()->whereHas('assessorAssignments', fn ($query) => $query->where('assessor_id', $request->user()->id))->exists(), 403);
        $search = mb_strtolower(trim((string) $request->query('search')));
        $assignments = $this->assignments($request, $program->id);

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
        })->filter(fn ($simulation) => ! $search
            || str_contains(mb_strtolower($simulation->program->name), $search)
            || str_contains(mb_strtolower($simulation->scenario->type->name), $search)
            || str_contains(mb_strtolower($simulation->scenario->simulationThreePackageLabel() ?? ''), $search))
          ->sortBy(fn ($simulation) => $simulation->scenario->type->sequence ?? 999)
          ->values();

        return view('pages.assessor.monitoring.program', [
            'program' => $program,
            'title' => 'Monitoring Simulasi',
            'simulations' => $simulations,
        ]);
    }

    private function assignments(Request $request, ?int $programId = null)
    {
        return AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->when($programId, fn ($query) => $query->whereHas('programSimulation', fn ($q) => $q->where('assessment_program_id', $programId)))
            ->with([
                'programSimulation.program.participants.user',
                'programSimulation.program.participants.sessions.reviews',
                'programSimulation.program.participants.sessions.programSimulation.scenario',
                'programSimulation.scenario.type',
                'programSimulation.sessions.events',
                'programSimulation.sessions.reviews',
            ])
            ->latest('assigned_at')
            ->get();
    }
}
