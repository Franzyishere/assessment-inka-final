<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentProgram;
use App\Models\SimulationSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssessmentResultArchiveController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_strtolower(trim((string) $request->query('search')));
        AssessmentProgram::syncLifecycle();
        $programs = AssessmentProgram::query()->whereNotNull('archived_at')
            ->when($search !== '', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%']))
            ->withCount(['participants', 'simulations'])->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(10)->withQueryString();

        return view('pages.admin.assessment-programs.index', compact('programs') + ['title' => 'Arsip Program Assessment', 'archiveMode' => true]);
    }

    public function show(Request $request, int $program)
    {
        $program = AssessmentProgram::findOrFail($program);
        $search = mb_strtolower(trim((string) $request->query('search')));
        $participants = $program->participants()->with([
            'user', 'sessions.programSimulation.scenario.type', 'sessions.submissions', 'sessions.reviews.assessor',
        ])->when($search !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user
            ->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$search.'%']))))
            ->orderBy('id')->paginate(15)->withQueryString();

        return view('pages.admin.result-archives.show', compact('program', 'participants') + ['title' => 'Hasil Program Assessment']);
    }

    public function file(int $program, SimulationSubmission $submission)
    {
        AssessmentProgram::findOrFail($program);
        abort_unless($submission->session->programSimulation->assessment_program_id === $program, 404);
        abort_unless($submission->storage_path && Storage::disk('local')->exists($submission->storage_path), 404);

        return Storage::disk('local')->download($submission->storage_path, $submission->original_filename ?: 'presentasi.pdf', [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
