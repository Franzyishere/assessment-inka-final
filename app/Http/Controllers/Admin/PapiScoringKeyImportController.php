<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewPapiScoringImportRequest;
use App\Models\PsychologicalTestVersion;
use App\Models\User;
use App\Services\PsychologicalTests\PapiScoringKeyImportService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PapiScoringKeyImportController extends Controller
{
    public function create(PsychologicalTestVersion $version): View
    {
        abort_if($version->status !== 'draft', 403, 'Scoring key versi non-draft tidak dapat diubah.');

        return view('pages.admin.recruitment.psychotests.scoring-import', [
            'title' => 'Import Scoring Key PAPI',
            'version' => $version,
            'previewRows' => session($this->key($version)),
        ]);
    }

    public function template(PapiScoringKeyImportService $service): BinaryFileResponse
    {
        return $service->templateResponse();
    }

    public function preview(PreviewPapiScoringImportRequest $request, PsychologicalTestVersion $version, PapiScoringKeyImportService $service): RedirectResponse
    {
        abort_if($version->status !== 'draft', 403);
        $request->session()->put($this->key($version), $service->read($request->file('scoring_file')->getRealPath()));

        return to_route('admin.recruitment.psychotests.scoring.import.create', $version)->with('status', 'Preview scoring key berhasil dibuat.');
    }

    public function store(Request $request, PsychologicalTestVersion $version, PapiScoringKeyImportService $service): RedirectResponse
    {
        abort_unless($request->user()->hasRole(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN), 403);
        $rows = $request->session()->get($this->key($version));
        if (! is_array($rows)) {
            return back()->with('error', 'Preview sudah tidak tersedia. Unggah kembali file scoring key.');
        }

        $service->import($version, $rows);
        $request->session()->forget($this->key($version));
        AuditLogger::record($request, 'papi_scoring_key.imported', $version, ['mapping_count' => 180]);

        return to_route('admin.recruitment.psychotests.show', $version)->with('success', '180 mapping scoring berhasil disimpan. Silakan validasi konfigurasi.');
    }

    private function key(PsychologicalTestVersion $version): string
    {
        return 'papi_scoring_preview.'.$version->id;
    }
}
