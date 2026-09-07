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
    public function index(Request $request): View
    {
        return view('pages.admin.assessment-programs.index', [
            'title' => 'Program Assessment',
            'programs' => AssessmentProgram::query()
                ->withCount(['participants', 'simulations'])
                ->when($request->filled('search'), fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower(trim((string) $request->query('search'))).'%']))
                ->orderByDesc('starts_at')->orderBy('name')->orderBy('id')
                ->paginate(10)->withQueryString(),
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:assessment_programs,id'],
        ], ['ids.required' => 'Pilih minimal satu program yang akan dihapus.']);

        $programs = AssessmentProgram::query()->whereIn('id', $validated['ids'])->get();
        $deletable = $programs->where('status', 'draft');

        foreach ($deletable as $program) {
            AuditLogger::record($request, 'assessment_program.deleted', $program, [
                'code' => $program->code,
                'name' => $program->name,
                'deletion_mode' => 'bulk',
            ]);
            $program->delete();
        }

        $skipped = $programs->count() - $deletable->count();
        $message = $deletable->count().' program draft berhasil dihapus.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' program aktif/selesai dilewati.';
        }

        return to_route('admin.assessment-programs.index')->with($deletable->isEmpty() ? 'error' : 'success', $message);
    }
}
