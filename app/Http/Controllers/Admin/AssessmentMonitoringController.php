<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentProgram;
use Illuminate\View\View;

class AssessmentMonitoringController extends Controller
{
    public function index(): View
    {
        $programs = AssessmentProgram::query()
            ->withCount(['participants', 'simulations'])
            ->orderByDesc('starts_at')->orderBy('name')->orderBy('id')->paginate(12);

        return view('pages.admin.monitoring.index', ['title' => 'Monitoring Assessment', 'programs' => $programs]);
    }

    public function show(AssessmentProgram $assessmentProgram): View
    {
        $assessmentProgram->load([
            'participants.user',
            'simulations.scenario.type',
            'simulations.sessions.participant.user',
            'simulations.sessions.reviews',
            'simulations.sessions.events',
        ]);

        $sessions = $assessmentProgram->simulations->pluck('sessions')->flatten();
        $expectedSessions = $assessmentProgram->simulations->sum(function ($simulation) use ($assessmentProgram) {
            if ($simulation->scenario->type->delivery_mode !== 'case_response') {
                return $assessmentProgram->participants->count();
            }

            $package = $simulation->scenario->simulation_package;

            return $assessmentProgram->participants->filter(fn ($participant) => $participant->simulationThreePackageKey() === $package)->count();
        });
        $submittedSessions = $sessions->where('status', 'submitted');
        $reviewedSessions = $submittedSessions->filter(fn ($session) => $session->reviews->contains('status', 'submitted'));

        return view('pages.admin.monitoring.show', [
            'title' => 'Detail Monitoring',
            'program' => $assessmentProgram,
            'sessions' => $sessions,
            'metrics' => [
                'expected' => $expectedSessions,
                'started' => $sessions->count(),
                'submitted' => $submittedSessions->count(),
                'reviewed' => $reviewedSessions->count(),
                'events' => $sessions->sum(fn ($session) => $session->events->count()),
                'progress' => $expectedSessions > 0 ? round(($submittedSessions->count() / $expectedSessions) * 100) : 0,
            ],
        ]);
    }
}
