<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentParticipant;
use App\Models\AssessorAssignment;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function updateSimulationThreeChoice(Request $request, AssessmentParticipant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'simulation_package' => ['required', 'string', 'in:ci_3,in_tray_3'],
        ], [
            'simulation_package.required' => 'Pilih paket Critical Incident 3 atau In-Tray 3.',
            'simulation_package.in' => 'Paket Simulasi 3 yang dipilih tidak valid.',
        ]);

        $hasProgramAssignment = AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->whereHas('programSimulation', fn ($query) => $query
                ->where('assessment_program_id', $participant->assessment_program_id))
            ->exists();
        abort_unless($hasProgramAssignment, 403);

        DB::transaction(function () use ($request, $participant, $validated): void {
            $lockedParticipant = AssessmentParticipant::query()->lockForUpdate()->findOrFail($participant->id);
            abort_unless($lockedParticipant->status === 'assigned', 422, 'Peserta sudah tidak aktif pada program assessment ini.');
            abort_unless($lockedParticipant->assessment_category === AssessmentParticipant::MADYA_CATEGORY, 422, 'Pemilihan CI/In-Tray hanya berlaku untuk peserta Promosi Spesialis Madya.');

            $simulationExists = $lockedParticipant->program->simulations()
                ->whereHas('scenario', fn ($query) => $query->where('simulation_package', $validated['simulation_package']))
                ->exists();
            abort_unless($simulationExists, 422, 'Paket yang dipilih belum tersedia pada Program Assessment ini.');

            $hasStartedSimulationThree = $lockedParticipant->sessions()
                ->whereHas('programSimulation.scenario', fn ($query) => $query
                    ->whereIn('simulation_package', ['ci_3', 'in_tray_3']))
                ->exists();
            abort_if($hasStartedSimulationThree, 409, 'Pilihan tidak dapat diubah karena peserta sudah memulai Simulasi 3.');

            $previousChoice = $lockedParticipant->simulation_three_choice;
            $lockedParticipant->update([
                'simulation_three_choice' => $validated['simulation_package'],
                'simulation_three_chosen_at' => now(),
            ]);

            AuditLogger::record($request, 'assessor.simulation_three_choice.updated', $lockedParticipant, [
                'previous_choice' => $previousChoice,
                'simulation_package' => $validated['simulation_package'],
                'program_id' => $lockedParticipant->assessment_program_id,
            ]);
        });

        return back()->with('success', 'Paket Simulasi 3 peserta berhasil ditetapkan.');
    }

    private function assignments(Request $request)
    {
        return AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
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
