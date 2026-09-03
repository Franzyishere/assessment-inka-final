<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StorePapiAnswerRequest;
use App\Models\PapiDimension;
use App\Models\PsychologicalTestSession;
use App\Models\RecruitmentBatchPsychologicalTest;
use App\Models\RecruitmentParticipant;
use App\Services\PsychologicalTests\PapiConfigurationService;
use App\Services\PsychologicalTests\PapiScoringService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RecruitmentExamController extends Controller
{
    public function index(Request $request): View
    {
        $participant = $this->participant($request);
        $batchIds = $participant->batches()->pluck('recruitment_batches.id');
        $assignments = RecruitmentBatchPsychologicalTest::query()
            ->whereIn('recruitment_batch_id', $batchIds)
            ->where('is_active', true)
            ->whereHas('version', fn ($query) => $query->where('status', 'published'))
            ->with(['batch', 'version.test', 'sessions' => fn ($query) => $query->where('recruitment_participant_id', $participant->id)])
            ->orderBy('available_from')
            ->orderBy('id')
            ->get();

        return view('pages.participant-recruitment.exams.index', compact('assignments', 'participant') + ['title' => 'Ujian Saya']);
    }

    public function instructions(Request $request, RecruitmentBatchPsychologicalTest $assignment): View
    {
        $participant = $this->authorizedParticipant($request, $assignment);
        $assignment->load(['batch', 'version.test']);
        $session = $assignment->sessions()->where('recruitment_participant_id', $participant->id)->latest('attempt_number')->first();

        return view('pages.participant-recruitment.exams.instructions', compact('assignment', 'session') + ['title' => 'Instruksi PAPI Kostick']);
    }

    public function start(Request $request, RecruitmentBatchPsychologicalTest $assignment): RedirectResponse
    {
        $participant = $this->authorizedParticipant($request, $assignment);
        $this->ensureAvailable($assignment);

        if (! $assignment->duration_minutes) {
            return back()->with('error', 'Durasi ujian belum ditentukan oleh admin. Ujian belum dapat dimulai.');
        }

        $session = $assignment->sessions()->where('recruitment_participant_id', $participant->id)->latest('attempt_number')->first();
        if ($session?->status === 'submitted') {
            return to_route('peserta-rekrutmen.exams.index')->with('error', 'Ujian ini sudah dikumpulkan.');
        }
        if ($session && $session->status === 'in_progress') {
            return to_route('peserta-rekrutmen.exams.take', $session);
        }
        if ($session && $session->status === 'expired') {
            return back()->with('error', 'Kesempatan ujian sudah berakhir. Hubungi admin jika memerlukan bantuan.');
        }

        $session = $assignment->sessions()->create([
            'recruitment_participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes($assignment->duration_minutes),
            'last_activity_at' => now(),
            'last_question_number' => 1,
            'session_token' => hash('sha256', Str::random(64)),
        ]);

        return to_route('peserta-rekrutmen.exams.take', $session)->with('success', 'Ujian dimulai. Timer berjalan dari server.');
    }

    public function take(Request $request, PsychologicalTestSession $session): View|RedirectResponse
    {
        $this->authorizeSession($request, $session);
        if ($session->status === 'submitted') {
            return to_route('peserta-rekrutmen.exams.index')->with('status', 'Ujian sudah dikumpulkan.');
        }
        if ($this->expireIfNeeded($session)) {
            return to_route('peserta-rekrutmen.exams.index')->with('error', 'Waktu ujian telah berakhir.');
        }

        $session->load(['assignment.version.questions.options', 'answers.option']);
        $questions = $session->assignment->version->questions->sortBy('number')->values();
        $answers = $session->answers->mapWithKeys(fn ($answer) => [$answer->psychological_question_id => $answer->option->code ?? null]);

        return view('pages.participant-recruitment.exams.take', compact('session', 'questions', 'answers') + ['title' => 'PAPI Kostick']);
    }

    public function answer(StorePapiAnswerRequest $request, PsychologicalTestSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);
        if ($session->status !== 'in_progress' || $this->expireIfNeeded($session)) {
            return response()->json(['message' => 'Sesi ujian sudah berakhir.'], 422);
        }

        $question = $session->assignment->version->questions()->with('options')->findOrFail($request->integer('question_id'));
        $option = $question->options->firstWhere('code', $request->string('choice')->toString());
        abort_unless($option, 422, 'Pilihan tidak sesuai dengan soal.');

        $session->answers()->updateOrCreate(
            ['psychological_question_id' => $question->id],
            ['psychological_question_option_id' => $option->id, 'answered_at' => now()],
        );
        $session->update(['last_activity_at' => now(), 'last_question_number' => $question->number]);

        return response()->json(['message' => 'Jawaban tersimpan.', 'answered_count' => $session->answers()->count()]);
    }

    public function submit(Request $request, PsychologicalTestSession $session, PapiConfigurationService $configuration, PapiScoringService $scoring): RedirectResponse
    {
        $this->authorizeSession($request, $session);
        if ($session->status !== 'in_progress') {
            return to_route('peserta-rekrutmen.exams.index')->with('error', 'Sesi ujian sudah tidak aktif.');
        }
        if ($this->expireIfNeeded($session)) {
            return to_route('peserta-rekrutmen.exams.index')->with('error', 'Waktu ujian telah berakhir. Jawaban tidak dapat dikumpulkan.');
        }

        $session->load(['assignment.version', 'answers.option']);
        $questionNumbers = $session->assignment->version->questions()->pluck('number', 'id');
        $answers = $session->answers->mapWithKeys(fn ($answer) => [
            $questionNumbers[$answer->psychological_question_id] => $answer->option->code,
        ])->all();
        try {
            $score = $scoring->score($answers, $configuration->scoringMap($session->assignment->version));
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        DB::transaction(function () use ($session, $score) {
            $result = $session->result()->updateOrCreate([], [
                'status' => 'scored',
                'total_role' => $score['total_role'],
                'total_need' => $score['total_need'],
                'scoring_version' => $session->assignment->version->version,
                'invalid_reason' => null,
                'scored_at' => now(),
            ]);
            $dimensionIds = PapiDimension::query()->pluck('id', 'code');
            foreach ($score['scores'] as $code => $value) {
                $result->scores()->updateOrCreate(['papi_dimension_id' => $dimensionIds[$code]], ['score' => $value]);
            }
            $session->update(['status' => 'submitted', 'submitted_at' => now(), 'last_activity_at' => now()]);
        });

        return to_route('peserta-rekrutmen.exams.index')->with('success', 'Jawaban berhasil dikumpulkan. Terima kasih telah menyelesaikan ujian.');
    }

    private function participant(Request $request): RecruitmentParticipant
    {
        return $request->user()->recruitmentParticipant()->firstOrFail();
    }

    private function authorizedParticipant(Request $request, RecruitmentBatchPsychologicalTest $assignment): RecruitmentParticipant
    {
        $participant = $this->participant($request);
        abort_unless($participant->batches()->whereKey($assignment->recruitment_batch_id)->exists(), 403);
        abort_unless($assignment->is_active && $assignment->version()->where('status', 'published')->exists(), 404);

        return $participant;
    }

    private function authorizeSession(Request $request, PsychologicalTestSession $session): void
    {
        abort_unless($session->recruitment_participant_id === $this->participant($request)->id, 403);
    }

    private function ensureAvailable(RecruitmentBatchPsychologicalTest $assignment): void
    {
        abort_if($assignment->available_from?->isFuture(), 403, 'Ujian belum dapat dimulai.');
        abort_if($assignment->available_until?->isPast(), 403, 'Periode ujian telah berakhir.');
    }

    private function expireIfNeeded(PsychologicalTestSession $session): bool
    {
        if ($session->expires_at?->isPast()) {
            $session->update(['status' => 'expired', 'last_activity_at' => now()]);

            return true;
        }

        return false;
    }
}
