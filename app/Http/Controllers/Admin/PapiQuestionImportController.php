<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewPapiQuestionImportRequest;
use App\Models\PsychologicalTestVersion;
use App\Services\PsychologicalTests\PapiQuestionImportService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PapiQuestionImportController extends Controller
{
    public function create(PsychologicalTestVersion $version): View
    {
        abort_if($version->status !== 'draft', 403, 'Soal versi non-draft tidak dapat diubah.');

        return view('pages.admin.recruitment.psychotests.import', ['title' => 'Import Soal PAPI', 'version' => $version, 'previewRows' => session($this->key($version))]);
    }

    public function template(PapiQuestionImportService $service): BinaryFileResponse
    {
        return $service->templateResponse();
    }

    public function preview(PreviewPapiQuestionImportRequest $request, PsychologicalTestVersion $version, PapiQuestionImportService $service): RedirectResponse
    {
        abort_if($version->status !== 'draft', 403);
        $request->session()->put($this->key($version), $service->read($request->file('question_file')->getRealPath()));

        return to_route('admin.recruitment.psychotests.questions.import.create', $version)->with('status', 'Preview soal berhasil dibuat.');
    }

    public function store(Request $request, PsychologicalTestVersion $version, PapiQuestionImportService $service): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'super_admin'), 403);
        $rows = $request->session()->get($this->key($version));
        if (! is_array($rows)) {
            return back()->with('error', 'Preview sudah tidak tersedia. Unggah kembali file soal.');
        }
        $service->import($version, $rows);
        $request->session()->forget($this->key($version));
        AuditLogger::record($request, 'papi_questions.imported', $version, ['question_count' => count($rows)]);

        return to_route('admin.recruitment.psychotests.show', $version)->with('success', '90 pasangan soal berhasil disimpan pada master versi ini.');
    }

    private function key(PsychologicalTestVersion $version): string
    {
        return 'papi_question_preview.'.$version->id;
    }
}
