<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AssessmentParticipant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentResultController extends Controller
{
    public function index(Request $request): View
    {
        $participations = AssessmentParticipant::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'program',
                'sessions' => fn ($query) => $query->where('status', 'submitted')->orderBy('submitted_at'),
                'sessions.programSimulation.scenario.type',
                'sessions.reviews' => fn ($query) => $query->where('status', 'submitted')->orderBy('reviewed_at'),
                'sessions.reviews.assessor',
            ])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        return view('pages.participant.results.index', [
            'title' => 'Hasil & Rekomendasi',
            'participations' => $participations,
        ]);
    }
}
