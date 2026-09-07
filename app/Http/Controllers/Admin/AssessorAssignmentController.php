<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAssessorAssignmentRequest;
use App\Models\AssessmentProgram;
use App\Models\AssessorAssignment;
use App\Models\SimulationReview;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessorAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = AssessmentProgram::query()
            ->whereHas('simulations')
            ->with(['simulations.scenario.type', 'simulations.assessorAssignments.assessor'])
            ->withCount(['simulations', 'participants'])
            ->orderByDesc('starts_at')->orderBy('name')->orderBy('id');

        if ($request->filled('program')) {
            $query->whereKey($request->integer('program'));
        }
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower(trim((string) $request->query('search'))).'%']);
        }

        return view('pages.admin.assessor-assignments.index', [
            'title' => 'Penugasan Asesor',
            'assessmentPrograms' => $query->paginate(15)->withQueryString(),
            'programs' => AssessmentProgram::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function edit(AssessmentProgram $assessmentProgram): View
    {
        $assessmentProgram->load(['simulations.scenario.type', 'simulations.assessorAssignments']);

        return view('pages.admin.assessor-assignments.edit', [
            'title' => 'Edit Tim Asesor Program',
            'program' => $assessmentProgram,
            'assessors' => User::where('role', User::ROLE_ASESOR)->orderBy('name')->get(),
            'selectedAssessors' => $assessmentProgram->simulations
                ->pluck('assessorAssignments')->flatten()->pluck('assessor_id')->unique()->values()->all(),
        ]);
    }

    public function update(UpdateAssessorAssignmentRequest $request, AssessmentProgram $assessmentProgram): RedirectResponse
    {
        $assessorIds = collect($request->validated('assessor_ids', []))->unique()->values();
        $assessmentProgram->load('simulations.assessorAssignments');
        $before = $assessmentProgram->simulations
            ->pluck('assessorAssignments')->flatten()->pluck('assessor_id')->unique()->values()->all();
        $removedIds = collect($before)->diff($assessorIds);
        $hasReviews = SimulationReview::whereIn('assessor_id', $removedIds)
            ->whereHas('session', fn ($query) => $query->whereIn('assessment_program_simulation_id', $assessmentProgram->simulations->pluck('id')))
            ->exists();
        if ($hasReviews) {
            throw ValidationException::withMessages(['assessor_ids' => 'Asesor yang sudah memiliki penilaian tidak dapat dilepas dari tim program.']);
        }

        DB::transaction(function () use ($request, $assessmentProgram, $assessorIds): void {
            foreach ($assessmentProgram->simulations as $programSimulation) {
                $programSimulation->assessorAssignments()->whereNotIn('assessor_id', $assessorIds)->delete();
                foreach ($assessorIds as $assessorId) {
                    AssessorAssignment::firstOrCreate(
                        ['assessment_program_simulation_id' => $programSimulation->id, 'assessor_id' => $assessorId],
                        ['assigned_by' => $request->user()->id, 'assigned_at' => now()]
                    );
                }
            }
        });

        AuditLogger::record($request, 'assessor_assignment.updated', $assessmentProgram, [
            'before' => $before,
            'after' => $assessorIds->all(),
            'simulation_count' => $assessmentProgram->simulations->count(),
        ]);

        return to_route('admin.assessor-assignments.index')->with('success', 'Tim asesor berhasil diterapkan ke seluruh simulasi program.');
    }
}
