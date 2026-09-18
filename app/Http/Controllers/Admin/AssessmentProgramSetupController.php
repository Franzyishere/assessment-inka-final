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
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentProgramSetupController extends Controller
{
    public function edit(AssessmentProgram $assessmentProgram): View
    {
        $assessmentProgram->load(['simulations.assessorAssignments', 'participants']);
        $catalog = SimulationCatalog::forProgram($assessmentProgram, request()->user()->id);

        return view('pages.admin.assessment-programs.setup', [
            'title' => 'Atur Program Assessment',
            'program' => $assessmentProgram,
            'scenarios' => $catalog,
            'participants' => User::where('role', User::ROLE_PESERTA_ASSESSMENT)->orderBy('name')->get(),
            'assessors' => User::where('role', User::ROLE_ASESOR)->orderBy('name')->get(),
            'selectedScenarios' => $assessmentProgram->simulations->pluck('simulation_scenario_id')->all(),
            'selectedParticipants' => $assessmentProgram->participants->pluck('user_id')->all(),
            'selectedParticipantCategories' => $assessmentProgram->participants->pluck('assessment_category', 'user_id')->all(),
            'selectedParticipantChoices' => $assessmentProgram->participants->pluck('simulation_three_choice', 'user_id')->all(),
            'simulationThreeOptions' => $catalog->filter(fn ($scenario) => $scenario->type->delivery_mode === 'case_response')
                ->mapWithKeys(fn ($scenario) => [$scenario->simulation_package => $scenario->simulationThreePackageLabel()])->all(),
            'assessmentCategories' => AssessmentParticipant::CATEGORIES,
            'selectedAssessors' => $assessmentProgram->simulations
                ->pluck('assessorAssignments')->flatten()->pluck('assessor_id')->unique()->values()->all(),
        ]);
    }

    public function update(UpdateAssessmentProgramSetupRequest $request, AssessmentProgram $assessmentProgram): RedirectResponse
    {
        return DB::transaction(function () use ($request, $assessmentProgram): RedirectResponse {
            $assessmentProgram = AssessmentProgram::query()->lockForUpdate()->findOrFail($assessmentProgram->id);
            $data = $request->validated();
            $catalog = SimulationCatalog::forProgram($assessmentProgram, $request->user()->id);
            $scenarioIds = $catalog->pluck('id');
            $usesSharedMaterials = $catalog->contains(fn ($scenario) => $scenario->usesSharedSimulationThreeMaterial());
            $upgradingMaterials = $usesSharedMaterials && ! $assessmentProgram->usesSharedSimulationThree();
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
            $usesCurrentPackages = collect(array_keys(SimulationScenario::SIMULATION_THREE_PACKAGES))
                ->diff($simulationThreePackages)->isEmpty();
            if ($simulationThreePackages->isNotEmpty()) {
                foreach ($participantIds as $participantId) {
                    $category = $data['participant_categories'][$participantId] ?? null;
                    $requiredPackages = $usesCurrentPackages ? array_keys(SimulationScenario::SIMULATION_THREE_PACKAGES)
                        : ($simulationThreePackages->contains('ci') && $simulationThreePackages->contains('in_tray') ? ['ci', 'in_tray'] : match ($category) {
                        'grade_1_to_2', 'promotion_specialist_pratama', 'promotion_spv' => ['ci_1'],
                        'grade_2_to_3', 'promotion_specialist_young' => ['ci_2'],
                        'grade_3_to_4' => ['ci_3'],
                        AssessmentParticipant::MADYA_CATEGORY => ['ci_3', 'in_tray_3'],
                        'promotion_m' => ['in_tray_1'],
                        'promotion_sm' => ['in_tray_2'],
                        default => [],
                    });
                    if (! $category || collect($requiredPackages)->diff($simulationThreePackages)->isNotEmpty()) {
                        throw ValidationException::withMessages([
                            "participant_categories.{$participantId}" => 'Tujuan assessment peserta harus memiliki paket materi Simulasi 3 yang dipilih.',
                        ]);
                    }
                }
            }

            foreach ($participantIds as $participantId) {
                $choice = $data['participant_simulation_three_choices'][$participantId] ?? null;
                if (! $choice) {
                    continue;
                }
                if (! $simulationThreePackages->contains($choice)) {
                    throw ValidationException::withMessages(["participant_simulation_three_choices.{$participantId}" => 'Paket Simulasi 3 ini tidak tersedia dalam program.']);
                }
                $scenario = $catalog->first(fn ($item) => $item->simulation_package === $choice);
                $material = $scenario?->materialPages()->first();
                if (! $material?->attachment_path || ! Storage::disk('local')->exists($material->attachment_path)) {
                    throw ValidationException::withMessages(["participant_simulation_three_choices.{$participantId}" => 'Materi PDF paket ini belum siap. Unggah materi melalui Bank Simulasi terlebih dahulu.']);
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

            DB::transaction(function () use ($request, $assessmentProgram, $scenarioIds, $participantIds, $assessorIds, $data, $upgradingMaterials, $simulationThreePackages): void {
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
                    $oldChoice = $participant->simulation_three_choice;
                    $newChoice = array_key_exists($participantId, $data['participant_simulation_three_choices'] ?? [])
                        ? ($data['participant_simulation_three_choices'][$participantId] ?: null)
                        : ($simulationThreePackages->contains($oldChoice) ? $oldChoice : null);
                    if ($participant->sessions()->whereHas('programSimulation.scenario.type', fn ($query) => $query->where('delivery_mode', 'case_response'))->exists()
                        && $newChoice !== $oldChoice) {
                        throw ValidationException::withMessages(["participant_simulation_three_choices.{$participantId}" => 'Pilihan Simulasi 3 terkunci karena peserta sudah memulai pengerjaan.']);
                    }
                    if ($upgradingMaterials || $participant->assessment_category !== $category) {
                        $newChoice = array_key_exists($participantId, $data['participant_simulation_three_choices'] ?? []) ? $newChoice : null;
                    }
                    $attributes += ['simulation_three_choice' => $newChoice, 'simulation_three_chosen_at' => $newChoice ? ($newChoice === $oldChoice ? $participant->simulation_three_chosen_at : now()) : null];
                    $participant->update($attributes);
                    if ($newChoice !== $oldChoice) {
                        AuditLogger::record($request, 'admin.simulation_three_choice.updated', $participant, [
                            'previous_choice' => $oldChoice, 'simulation_package' => $newChoice,
                            'program_id' => $assessmentProgram->id,
                        ]);
                    }
                }
            });

            return to_route('admin.assessment-programs.index')->with('success', 'Susunan program dan penugasan berhasil disimpan.');
        });
    }
}
