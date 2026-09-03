<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PsychologicalResult;
use App\Models\RecruitmentBatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentPsychologicalResultController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $batches = RecruitmentBatch::query()
            ->whereHas('psychologicalTests.sessions.result', fn ($query) => $query->where('status', 'scored'))
            ->withCount('participants')
            ->addSelect([
                'scored_results_count' => PsychologicalResult::query()
                    ->selectRaw('count(*)')
                    ->join('psychological_test_sessions', 'psychological_test_sessions.id', '=', 'psychological_results.psychological_test_session_id')
                    ->join('recruitment_batch_psychological_tests', 'recruitment_batch_psychological_tests.id', '=', 'psychological_test_sessions.recruitment_batch_psychological_test_id')
                    ->whereColumn('recruitment_batch_psychological_tests.recruitment_batch_id', 'recruitment_batches.id')
                    ->where('psychological_results.status', 'scored'),
            ])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.recruitment.results.index', compact('batches', 'search') + ['title' => 'Hasil Psikotes']);
    }

    public function batch(Request $request, RecruitmentBatch $batch): View
    {
        $search = trim($request->string('search')->toString());
        $results = PsychologicalResult::query()
            ->where('status', 'scored')
            ->whereHas('session.assignment', fn ($query) => $query->where('recruitment_batch_id', $batch->id))
            ->whereHas('session.participant.user')
            ->with(['session.participant.user', 'session.assignment.version.test'])
            ->when($search !== '', fn ($query) => $query->whereHas('session.participant.user', fn ($userQuery) => $userQuery
                ->where(fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.recruitment.results.batch', compact('batch', 'results', 'search') + ['title' => 'Hasil Psikotes '.$batch->name]);
    }

    public function show(PsychologicalResult $result): View
    {
        abort_unless($result->status === 'scored', 404);
        $result->load(['scores.dimension', 'session.participant.user', 'session.assignment.batch', 'session.assignment.version.test']);
        $scores = $result->scores
            ->filter(fn ($score) => $score->dimension !== null)
            ->mapWithKeys(fn ($score) => [$score->dimension->code => $score->score]);
        $orderedCodes = [
            'N', 'G', 'A',
            'L', 'P', 'I',
            'T', 'V', 'X',
            'O', 'B', 'S',
            'C', 'D', 'R',
            'Z', 'E', 'K',
            'F', 'W',
        ];

        return view('pages.admin.recruitment.results.show', compact('result', 'scores', 'orderedCodes') + ['title' => 'Profil PAPI Peserta']);
    }
}
