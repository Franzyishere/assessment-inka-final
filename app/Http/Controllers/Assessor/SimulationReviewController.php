<?php

namespace App\Http\Controllers\Assessor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentProgram;
use App\Models\AssessorAssignment;
use App\Models\SimulationSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SimulationReviewController extends Controller
{
    public function index(Request $request): View
    {
        $assessorId = $request->user()->id;
        $search = mb_strtolower(trim((string) $request->query('search')));
        $programs = AssessmentProgram::query()
            ->whereHas('simulations', fn ($query) => $query
                ->whereHas('assessorAssignments', fn ($assignment) => $assignment->where('assessor_id', $assessorId))
                ->whereHas('sessions', fn ($session) => $session->where('status', 'submitted')))
            ->with(['simulations' => fn ($query) => $query
                ->whereHas('assessorAssignments', fn ($assignmentQuery) => $assignmentQuery->where('assessor_id', $assessorId))
                ->with(['sessions' => fn ($sessionQuery) => $sessionQuery
                    ->where('status', 'submitted')
                    ->with('participant.user')])])
            ->when($search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
            ->orderByDesc('starts_at')
            ->orderBy('name')
            ->paginate(9)->withQueryString();

        return view('pages.assessor.reviews.index', ['title' => 'Hasil Assessment', 'programs' => $programs]);
    }

    public function program(Request $request, AssessmentProgram $program): View
    {
        $assessorId = $request->user()->id;
        $search = mb_strtolower(trim((string) $request->query('search')));
        $assignmentIds = AssessorAssignment::query()
            ->where('assessor_id', $assessorId)
            ->whereHas('programSimulation', fn ($query) => $query->where('assessment_program_id', $program->id))
            ->pluck('assessment_program_simulation_id');

        abort_if($assignmentIds->isEmpty(), 403);

        $sessions = SimulationSession::query()
            ->whereIn('assessment_program_simulation_id', $assignmentIds)
            ->where('status', 'submitted')
            ->when($search, fn ($query) => $query->whereHas('participant.user', fn ($userQuery) => $userQuery
                ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])))
            ->with(['programSimulation.scenario.type', 'participant.user'])
            ->withExists('submissions')
            ->get()
            ->sortBy(fn ($session) => mb_strtolower($session->participant->user->name).'|'.str_pad((string) ($session->programSimulation->scenario->type->sequence ?? 0), 3, '0', STR_PAD_LEFT))
            ->values();

        return view('pages.assessor.reviews.program', [
            'title' => 'Hasil Assessment Peserta',
            'program' => $program,
            'participantSessions' => $sessions->groupBy('assessment_participant_id'),
        ]);
    }

    public function edit(Request $request, SimulationSession $session): View
    {
        $this->authorizeSession($request, $session);
        abort_unless($session->status === 'submitted', 403, 'Peserta belum mengumpulkan simulasi.');
        $session->load(['programSimulation.program', 'programSimulation.scenario.type', 'programSimulation.scenario.materialPages', 'participant.user', 'submissions', 'events']);
        $submission = $session->submissions->sortByDesc('revision')->first();
        $responses = $submission && ! $submission->storage_path ? (json_decode($submission->response_text ?? '{}', true) ?: []) : [];

        return view('pages.assessor.reviews.edit', compact('session', 'submission', 'responses') + ['title' => 'Lembar Jawaban Peserta']);
    }

    public function update(Request $request, SimulationSession $session): never
    {
        $this->authorizeSession($request, $session);
        // Keep the legacy route blocked without modifying historical reviews.
        abort(403, 'Penilaian dinonaktifkan. Jawaban peserta hanya dapat dilihat dan diunduh.');
    }

    public function download(Request $request, SimulationSession $session)
    {
        $this->authorizeSession($request, $session);
        abort_unless($session->status === 'submitted', 403, 'Peserta belum mengumpulkan simulasi.');

        $session->load([
            'programSimulation.program',
            'programSimulation.scenario.type',
            'programSimulation.scenario.materialPages',
            'participant.user',
            'submissions',
        ]);

        $submission = $session->submissions->sortByDesc('revision')->first();
        abort_unless($submission, 404, 'Belum ada jawaban yang tersimpan.');

        $format = strtolower($request->query('format', 'pdf'));
        abort_unless(in_array($format, ['pdf', 'word'], true), 422, 'Format unduhan tidak valid.');
        $participantName = $session->participant->user->name;
        $simulationName = $session->programSimulation->scenario->simulationThreePackageLabel()
            ?? $session->programSimulation->scenario->type->name;
        $sanitizedFilename = Str::slug("Jawaban-{$simulationName}-{$participantName}");

        if ($submission->storage_path) {
            abort_unless(Storage::disk('local')->exists($submission->storage_path), 404, 'Berkas jawaban tidak ditemukan.');
            if ($format === 'pdf') {
                return Storage::disk('local')->download($submission->storage_path, $submission->original_filename ?: "{$sanitizedFilename}.pdf");
            }

            $data = [
                'session' => $session,
                'submission' => $submission,
                'simulationName' => $simulationName,
                'responses' => [],
                'materialPages' => collect(),
                'isWord' => true,
            ];
            $html = view('exports.submission-document', $data)->render();

            return response(\App\Support\AnswerDiagramExport::wordDocument($html), 200, [
                'Content-Type' => 'application/msword; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$sanitizedFilename.'.doc"',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        }

        $responses = json_decode($submission->response_text ?? '{}', true) ?: [];
        $materialPages = $session->programSimulation->scenario->materialPages;

        $data = [
            'session' => $session,
            'submission' => $submission,
            'simulationName' => $simulationName,
            'responses' => $responses,
            'materialPages' => $materialPages,
            'isWord' => ($format === 'word'),
        ];

        if ($format === 'word') {
            $html = view('exports.submission-document', $data)->render();

            return response(\App\Support\AnswerDiagramExport::wordDocument($html), 200, [
                'Content-Type' => 'application/msword; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$sanitizedFilename.'.doc"',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        }

        $pdf = Pdf::loadView('exports.submission-document', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        return $pdf->download("{$sanitizedFilename}.pdf");
    }

    private function authorizeSession(Request $request, SimulationSession $session): void
    {
        abort_unless(AssessorAssignment::where('assessment_program_simulation_id', $session->assessment_program_simulation_id)
            ->where('assessor_id', $request->user()->id)->exists(), 403);
    }
}
