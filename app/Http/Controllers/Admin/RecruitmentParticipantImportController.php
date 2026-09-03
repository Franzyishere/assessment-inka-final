<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewRecruitmentParticipantImportRequest;
use App\Models\RecruitmentBatch;
use App\Services\Recruitment\RecruitmentParticipantImportService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecruitmentParticipantImportController extends Controller
{
    public function create(RecruitmentBatch $recruitment): View
    {
        return view('pages.admin.recruitment.import', [
            'title' => 'Import Peserta Rekrutmen',
            'batch' => $recruitment,
            'previewRows' => session($this->sessionKey($recruitment)),
        ]);
    }

    public function template(RecruitmentParticipantImportService $service): BinaryFileResponse
    {
        return $service->templateResponse();
    }

    public function preview(
        PreviewRecruitmentParticipantImportRequest $request,
        RecruitmentBatch $recruitment,
        RecruitmentParticipantImportService $service
    ): RedirectResponse {
        $rows = $service->read($request->file('participant_file')->getRealPath());
        $request->session()->put($this->sessionKey($recruitment), $rows);

        return to_route('admin.recruitment.import.create', $recruitment)
            ->with('status', 'Preview berhasil dibuat. Periksa seluruh baris sebelum mengonfirmasi import.');
    }

    public function store(
        Request $request,
        RecruitmentBatch $recruitment,
        RecruitmentParticipantImportService $service
    ): BinaryFileResponse|RedirectResponse {
        $rows = $request->session()->get($this->sessionKey($recruitment));
        if (! is_array($rows) || $rows === []) {
            return to_route('admin.recruitment.import.create', $recruitment)
                ->with('error', 'Data preview sudah tidak tersedia. Unggah kembali file peserta.');
        }

        $rows = $service->validateRows($rows);
        if (collect($rows)->contains(fn ($row) => ! $row['valid'])) {
            $request->session()->put($this->sessionKey($recruitment), $rows);

            return to_route('admin.recruitment.import.create', $recruitment)
                ->with('error', 'Data berubah atau mengalami konflik. Periksa preview kembali.');
        }

        $credentials = $service->import($recruitment, $rows);
        $request->session()->forget($this->sessionKey($recruitment));
        AuditLogger::record($request, 'recruitment_participants.imported', $recruitment, [
            'row_count' => count($rows),
            'new_account_count' => collect($credentials)->where('is_new', true)->count(),
        ]);

        return $service->credentialResponse($recruitment, $credentials);
    }

    public function cancel(Request $request, RecruitmentBatch $recruitment): RedirectResponse
    {
        $request->session()->forget($this->sessionKey($recruitment));

        return to_route('admin.recruitment.show', $recruitment)->with('status', 'Preview import dibatalkan.');
    }

    private function sessionKey(RecruitmentBatch $batch): string
    {
        return 'recruitment_import_preview.'.$batch->id;
    }
}
