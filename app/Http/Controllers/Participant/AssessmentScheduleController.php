<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentScheduleController extends Controller
{
    public function index(Request $request): View
    {
        AssessmentProgram::activateDuePrograms();

        $participations = AssessmentParticipant::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'assigned')
            ->with([
                'program.simulations' => fn ($query) => $query
                    ->with(['scenario.type'])
                    ->orderByRaw('COALESCE(opens_at, created_at)')
                    ->orderBy('id'),
                'sessions',
            ])
            ->latest('assigned_at')
            ->get();

        $participations->each(function (AssessmentParticipant $participation): void {
            $participation->program->setRelation('simulations', $participation->program->simulations
                ->filter(function ($simulation) use ($participation): bool {
                    if ($simulation->scenario->type->delivery_mode !== 'case_response') {
                        return true;
                    }

                    if ($participation->requiresSimulationThreeChoice()) {
                        return $simulation->scenario->simulation_package === $participation->pendingSimulationThreePackage();
                    }

                    return $simulation->scenario->simulation_package === $participation->simulationThreePackageKey();
                })
                ->sortBy(fn ($simulation) => $simulation->scenario->type->sequence ?? 999)
                ->values());
        });

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->query('search')));
            $participations = $participations->filter(fn ($participation) => str_contains(mb_strtolower($participation->program->name), $search)
                || $participation->program->simulations->contains(fn ($simulation) => str_contains(mb_strtolower($simulation->scenario->type->name), $search)
                    || str_contains(mb_strtolower($simulation->scenario->simulationThreePackageLabel() ?? ''), $search)))->values();
        }

        return view('pages.participant.schedule.index', [
            'title' => 'Jadwal Assessment',
            'participations' => $participations,
        ]);
    }
}
