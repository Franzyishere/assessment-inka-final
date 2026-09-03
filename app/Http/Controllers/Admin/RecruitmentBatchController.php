<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignPapiToBatchRequest;
use App\Http\Requests\Admin\StoreRecruitmentBatchRequest;
use App\Http\Requests\Admin\UpdateRecruitmentBatchRequest;
use App\Models\PsychologicalTestVersion;
use App\Models\RecruitmentBatch;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentBatchController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.recruitment.index', [
            'title' => 'Rekrutmen',
            'batches' => RecruitmentBatch::query()
                ->withCount('participants')
                ->orderByDesc('starts_at')
                ->orderByDesc('id')
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.recruitment.create', ['title' => 'Buat Batch Rekrutmen']);
    }

    public function store(StoreRecruitmentBatchRequest $request): RedirectResponse
    {
        $batch = RecruitmentBatch::create([...$request->validated(), 'created_by' => $request->user()->id]);
        AuditLogger::record($request, 'recruitment_batch.created', $batch, ['name' => $batch->name]);

        return to_route('admin.recruitment.show', $batch)->with('success', 'Batch rekrutmen berhasil dibuat.');
    }

    public function show(RecruitmentBatch $recruitment): View
    {
        return view('pages.admin.recruitment.show', [
            'title' => $recruitment->name,
            'batch' => $recruitment,
            'participants' => $recruitment->participants()
                ->with('user')
                ->orderBy('participant_number')
                ->paginate(20),
            'publishedPapiVersions' => PsychologicalTestVersion::query()
                ->where('status', 'published')
                ->whereHas('test', fn ($query) => $query->where('code', 'PAPI_KOSTICK'))
                ->orderByDesc('published_at')->get(),
            'psychologicalAssignments' => $recruitment->psychologicalTests()->with('version.test')->get(),
        ]);
    }

    public function edit(RecruitmentBatch $recruitment): View
    {
        return view('pages.admin.recruitment.edit', ['title' => 'Edit Batch Rekrutmen', 'batch' => $recruitment]);
    }

    public function update(UpdateRecruitmentBatchRequest $request, RecruitmentBatch $recruitment): RedirectResponse
    {
        $recruitment->update($request->validated());
        AuditLogger::record($request, 'recruitment_batch.updated', $recruitment, ['name' => $recruitment->name]);

        return to_route('admin.recruitment.show', $recruitment)->with('success', 'Batch rekrutmen berhasil diperbarui.');
    }

    public function destroy(Request $request, RecruitmentBatch $recruitment): RedirectResponse
    {
        if ($recruitment->status !== 'draft' || $recruitment->participants()->exists()) {
            return back()->with('error', 'Hanya batch draft tanpa peserta yang dapat dihapus.');
        }
        AuditLogger::record($request, 'recruitment_batch.deleted', $recruitment, ['name' => $recruitment->name]);
        $recruitment->delete();

        return to_route('admin.recruitment.index')->with('success', 'Batch rekrutmen berhasil dihapus.');
    }

    public function assignPsychologicalTest(AssignPapiToBatchRequest $request, RecruitmentBatch $recruitment): RedirectResponse
    {
        $data = $request->validated();
        $recruitment->psychologicalTests()->updateOrCreate(
            ['psychological_test_version_id' => $data['psychological_test_version_id']],
            [
                'available_from' => $data['available_from'] ?? null,
                'available_until' => $data['available_until'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'max_attempts' => 1,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ],
        );
        AuditLogger::record($request, 'recruitment_psychotest.assigned', $recruitment, ['version_id' => $data['psychological_test_version_id']]);

        return back()->with('success', 'Master PAPI berhasil ditugaskan ke batch ini.');
    }
}
