<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentProgramRequest;
use App\Http\Requests\Admin\UpdateAssessmentProgramRequest;
use App\Models\AssessmentProgram;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssessmentProgramController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.assessment-programs.index', [
            'title' => 'Program Assessment',
            'programs' => AssessmentProgram::query()
                ->withCount(['participants', 'simulations'])
                ->orderByDesc('starts_at')->orderBy('name')->orderBy('id')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.assessment-programs.create', ['title' => 'Buat Program Assessment']);
    }

    public function store(StoreAssessmentProgramRequest $request): RedirectResponse
    {
        AssessmentProgram::create([
            ...$request->validated(),
            'code' => 'ASM-'.Str::ulid(),
            'created_by' => $request->user()->id,
        ]);

        return to_route('admin.assessment-programs.index')->with('success', 'Program assessment berhasil dibuat.');
    }

    public function edit(AssessmentProgram $assessmentProgram): View
    {
        return view('pages.admin.assessment-programs.edit', [
            'title' => 'Edit Program Assessment',
            'program' => $assessmentProgram,
        ]);
    }

    public function update(UpdateAssessmentProgramRequest $request, AssessmentProgram $assessmentProgram): RedirectResponse
    {
        $assessmentProgram->update($request->validated());

        return to_route('admin.assessment-programs.index')->with('success', 'Program assessment berhasil diperbarui.');
    }

    public function destroy(Request $request, AssessmentProgram $assessmentProgram): RedirectResponse
    {
        if ($assessmentProgram->status !== 'draft') {
            return back()->with('error', 'Program hanya dapat dihapus saat masih berstatus draft.');
        }

        AuditLogger::record($request, 'assessment_program.deleted', $assessmentProgram, [
            'code' => $assessmentProgram->code,
            'name' => $assessmentProgram->name,
        ]);
        $assessmentProgram->delete();

        return to_route('admin.assessment-programs.index')->with('success', 'Program assessment berhasil dihapus.');
    }
}
