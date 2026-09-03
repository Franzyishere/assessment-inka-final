<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePapiVersionRequest;
use App\Models\PsychologicalTest;
use App\Models\PsychologicalTestVersion;
use App\Services\PsychologicalTests\PapiConfigurationService;
use App\Support\AuditLogger;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PapiConfigurationController extends Controller
{
    public function index(): View
    {
        $test = PsychologicalTest::where('code', 'PAPI_KOSTICK')->firstOrFail();

        return view('pages.admin.recruitment.psychotests.index', [
            'title' => 'Master Psikotes',
            'test' => $test,
            'versions' => $test->versions()->withCount('questions')->orderByDesc('id')->get(),
        ]);
    }

    public function store(StorePapiVersionRequest $request, PsychologicalTest $psychologicalTest): RedirectResponse
    {
        abort_unless($psychologicalTest->code === 'PAPI_KOSTICK', 404);
        $version = $psychologicalTest->versions()->create([
            ...$request->validated(), 'item_count' => 90, 'expected_role_total' => 45, 'expected_need_total' => 45, 'status' => 'draft',
        ]);
        AuditLogger::record($request, 'papi_version.created', $version, ['version' => $version->version]);

        return to_route('admin.recruitment.psychotests.show', $version)->with('success', 'Versi PAPI berhasil dibuat.');
    }

    public function show(PsychologicalTestVersion $version): View
    {
        $version->loadCount('questions')->load(['test', 'questions.options.scoringRule']);

        return view('pages.admin.recruitment.psychotests.show', [
            'title' => 'PAPI '.$version->version,
            'version' => $version,
            'optionCount' => $version->questions->sum(fn ($question) => $question->options->count()),
            'mappingCount' => $version->questions->sum(fn ($question) => $question->options->whereNotNull('scoringRule')->count()),
        ]);
    }

    public function validateConfiguration(Request $request, PsychologicalTestVersion $version, PapiConfigurationService $service): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'super_admin'), 403);
        if ($version->status !== 'draft') {
            return back()->with('error', 'Hanya versi draft yang dapat divalidasi.');
        }
        try {
            $service->scoringMap($version);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $version->update(['status' => 'validated', 'validated_at' => now(), 'validated_by' => $request->user()->id]);
        AuditLogger::record($request, 'papi_version.validated', $version, ['version' => $version->version]);

        return back()->with('success', 'Konfigurasi lengkap dan berhasil divalidasi.');
    }

    public function publish(Request $request, PsychologicalTestVersion $version): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        if ($version->status !== 'validated') {
            return back()->with('error', 'Versi harus berstatus validated sebelum dipublikasikan.');
        }
        $version->update(['status' => 'published', 'published_at' => now(), 'published_by' => $request->user()->id]);
        AuditLogger::record($request, 'papi_version.published', $version, ['version' => $version->version]);

        return back()->with('success', 'Versi PAPI dipublikasikan dan siap ditugaskan ke batch.');
    }
}
