<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentProgramRequest;
use App\Http\Requests\Admin\UpdateAssessmentProgramRequest;
use App\Models\AssessmentProgram;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssessmentProgramController extends Controller
{
    public function index(Request $request): View
    {
        AssessmentProgram::syncLifecycle();

        return view('pages.admin.assessment-programs.index', [
            'title' => 'Program Assessment',
            'programs' => AssessmentProgram::query()
                ->whereNull('archived_at')
                ->withCount(['participants', 'simulations'])
                ->when($request->filled('search'), fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower(trim((string) $request->query('search'))).'%']))
                ->orderByDesc('created_at')->orderByDesc('id')
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
            'auto_send_invitations' => true,
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
        DB::transaction(function () use ($request, $assessmentProgram) {
            $program = AssessmentProgram::query()->lockForUpdate()->findOrFail($assessmentProgram->id);
            $program->update($request->validated());
        });

        return to_route($assessmentProgram->archived_at ? 'admin.result-archives.index' : 'admin.assessment-programs.index')->with('success', 'Program assessment berhasil diperbarui.');
    }

    public function destroy(Request $request, AssessmentProgram $assessmentProgram): RedirectResponse
    {
        return DB::transaction(function () use ($request, $assessmentProgram) {
            $assessmentProgram = AssessmentProgram::query()->lockForUpdate()->findOrFail($assessmentProgram->id);
            if ($assessmentProgram->status === 'active') {
                return back()->with('error', 'Program berstatus aktif tidak dapat dihapus. Ubah status program terlebih dahulu jika ingin menghapus.');
            }

            AuditLogger::record($request, 'assessment_program.archived', $assessmentProgram, [
                'code' => $assessmentProgram->code,
                'name' => $assessmentProgram->name,
            ]);
            $assessmentProgram->archived_at ??= now();
            $assessmentProgram->save();

            return to_route('admin.assessment-programs.index')->with('success', 'Program dipindahkan ke Arsip Program Assessment. Akses dan penilaian asesor tetap tersedia.');
        });
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:assessment_programs,id'],
        ], ['ids.required' => 'Pilih minimal satu program yang akan dihapus.']);

        return DB::transaction(function () use ($request, $validated) {
            $programs = AssessmentProgram::query()->whereNull('archived_at')->whereIn('id', $validated['ids'])->orderBy('id')->lockForUpdate()->get();
            $deletable = $programs->where('status', '!==', 'active');

            foreach ($deletable as $program) {
                AuditLogger::record($request, 'assessment_program.archived', $program, [
                    'code' => $program->code,
                    'name' => $program->name,
                    'deletion_mode' => 'bulk',
                ]);
                $program->archived_at = now();
                $program->save();
            }

            $skipped = $programs->count() - $deletable->count();
            $message = $deletable->count().' program dipindahkan ke Arsip Program Assessment. Akses dan penilaian asesor tetap tersedia.';
            if ($skipped > 0) {
                $message .= ' '.$skipped.' program berstatus aktif dilewati.';
            }

            return to_route('admin.assessment-programs.index')->with($deletable->isEmpty() ? 'error' : 'success', $message);
        });
    }
}
