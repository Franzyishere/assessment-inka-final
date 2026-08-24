<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAssessmentProgramSetupRequest;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationReview;
use App\Models\SimulationScenario;
use App\Models\User;
use App\Support\SimulationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentProgramSetupController extends Controller
{
    public function edit(AssessmentProgram $assessmentProgram): View
    {
        $assessmentProgram->load(['simulations.assessorAssignments', 'participants']);
        $catalog = SimulationCatalog::ensure(request()->user()->id);

        return view('pages.admin.assessment-programs.setup', [
            'title' => 'Atur Program Assessment',
            'program' => $assessmentProgram,
            'scenarios' => $catalog,
            'participants' => User::where('role', User::ROLE_PESERTA_ASSESSMENT)->orderBy('name')->get(),
            'assessors' => User::where('role', User::ROLE_ASESOR)->orderBy('name')->get(),
            'selectedScenarios' => $assessmentProgram->simulations->pluck('simulation_scenario_id')->all(),
            'selectedParticipants' => $assessmentProgram->participants->pluck('user_id')->all(),
            'selectedParticipantCategories' => $assessmentProgram->participants->pluck('assessment_category', 'user_id')->all(),
            'assessmentCategories' => AssessmentParticipant::CATEGORIES,
            'selectedAssessors' => $assessmentProgram->simulations
                ->pluck('assessorAssignments')->flatten()->pluck('assessor_id')->unique()->values()->all(),
        ]);
    }

    public function update(UpdateAssessmentProgramSetupRequest $request, AssessmentProgram $assessmentProgram): RedirectResponse
    {
        $data = $request->validated();
        $scenarioIds = SimulationCatalog::ensure($request->user()->id)->pluck('id');
        $participantIds = collect($data['participant_ids'] ?? [])->unique();
        $assessorIds = collect($data['assessor_ids'] ?? [])->unique()->values();
        $simulationThreePackages = SimulationScenario::query()
            ->with('type')
            ->whereIn('id', $scenarioIds)
            ->whereHas('type', fn ($query) => $query->where('delivery_mode', 'case_response'))
            ->get()
            ->pluck('simulation_package')
            ->filter()
            ->unique();
        if ($simulationThreePackages->isNotEmpty()) {
            foreach ($participantIds as $participantId) {
                $category = $data['participant_categories'][$participantId] ?? null;
                $requiredPackages = match ($category) {
                    'grade_1_to_2', 'promotion_specialist_pratama', 'promotion_spv' => ['ci_1'],
                    'grade_2_to_3', 'promotion_specialist_young' => ['ci_2'],
                    'grade_3_to_4' => ['ci_3'],
                    AssessmentParticipant::MADYA_CATEGORY => ['ci_3', 'in_tray_3'],
                    'promotion_m' => ['in_tray_1'],
                    'promotion_sm' => ['in_tray_2'],
                    default => [],
                };
                if (! $category || collect($requiredPackages)->diff($simulationThreePackages)->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        "participant_categories.{$participantId}" => 'Tujuan assessment peserta harus memiliki paket materi Simulasi 3 yang dipilih.',
                    ]);
                }
            }
        }

        $removedSimulations = $assessmentProgram->simulations()
            ->whereNotIn('simulation_scenario_id', $scenarioIds)
            ->whereHas('sessions')
            ->exists();
        if ($removedSimulations) {
            throw ValidationException::withMessages(['simulation_ids' => 'Simulasi yang sudah memiliki sesi peserta tidak dapat dilepas dari program.']);
        }

        $removedParticipants = $assessmentProgram->participants()
            ->whereNotIn('user_id', $participantIds)
            ->whereHas('sessions')
            ->exists();
        if ($removedParticipants) {
            throw ValidationException::withMessages(['participant_ids' => 'Peserta yang sudah memiliki sesi simulasi tidak dapat dilepas dari program.']);
        }

        foreach ($assessmentProgram->participants()->whereIn('user_id', $participantIds)->get() as $participant) {
            $newCategory = $data['participant_categories'][$participant->user_id] ?? null;
            if ($participant->assessment_category !== $newCategory && $participant->sessions()->exists()) {
                throw ValidationException::withMessages([
                    "participant_categories.{$participant->user_id}" => 'Jenis assessment tidak dapat diubah karena peserta sudah memiliki sesi. Hubungi administrator sistem bila diperlukan koreksi data.',
                ]);
            }
        }

        $programSimulationIds = $assessmentProgram->simulations()->pluck('id');
        $removedAssessors = AssessorAssignment::whereIn('assessment_program_simulation_id', $programSimulationIds)
            ->pluck('assessor_id')->diff($assessorIds);
        $hasReviews = SimulationReview::whereIn('assessor_id', $removedAssessors)
            ->whereHas('session', fn ($query) => $query->whereIn('assessment_program_simulation_id', $programSimulationIds))
            ->exists();
        if ($hasReviews) {
            throw ValidationException::withMessages(['assessor_ids' => 'Asesor yang sudah memiliki penilaian tidak dapat dilepas dari tim program.']);
        }

        DB::transaction(function () use ($request, $assessmentProgram, $scenarioIds, $participantIds, $assessorIds, $data): void {
            $assessmentProgram->simulations()->whereNotIn('simulation_scenario_id', $scenarioIds)->delete();

            foreach ($scenarioIds as $scenarioId) {
                $programSimulation = AssessmentProgramSimulation::updateOrCreate(
                    ['assessment_program_id' => $assessmentProgram->id, 'simulation_scenario_id' => $scenarioId],
                    ['opens_at' => $assessmentProgram->starts_at, 'closes_at' => $assessmentProgram->ends_at, 'status' => 'scheduled']
                );

                $programSimulation->assessorAssignments()->whereNotIn('assessor_id', $assessorIds)->delete();
                foreach ($assessorIds as $assessorId) {
                    AssessorAssignment::firstOrCreate(
                        ['assessment_program_simulation_id' => $programSimulation->id, 'assessor_id' => $assessorId],
                        ['assigned_by' => $request->user()->id, 'assigned_at' => now()]
                    );
                }
            }

            $assessmentProgram->participants()->whereNotIn('user_id', $participantIds)->delete();
            foreach ($participantIds as $participantId) {
                $participant = $assessmentProgram->participants()->firstOrCreate(
                    ['user_id' => $participantId],
                    ['status' => 'assigned', 'assigned_at' => now()]
                );
                $category = $data['participant_categories'][$participantId] ?? null;
                $attributes = ['assessment_category' => $category];
                if ($participant->assessment_category !== $category && ! $participant->sessions()->whereHas('programSimulation.scenario.type', fn ($query) => $query->where('delivery_mode', 'case_response'))->exists()) {
                    $attributes += ['simulation_three_choice' => null, 'simulation_three_chosen_at' => null];
                }
                $participant->update($attributes);
            }
        });

        return to_route('admin.assessment-programs.setup.edit', $assessmentProgram)->with('success', 'Susunan program dan penugasan berhasil disimpan.');
    }
}
