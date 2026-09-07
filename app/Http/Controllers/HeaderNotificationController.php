<?php

namespace App\Http\Controllers;

use App\Models\SimulationSession;
use App\Models\SimulationReview;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HeaderNotificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = [];
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            $count = SimulationSession::where('status', 'submitted')->whereDoesntHave('reviews', fn ($q) => $q->where('status', 'submitted'))->count();
            if ($count) $items[] = ['title' => "$count submission belum memiliki penilaian final", 'url' => route('admin.monitoring.index')];
        } elseif ($user->role === 'asesor') {
            $count = SimulationSession::where('status', 'submitted')
                ->whereHas('programSimulation.assessorAssignments', fn ($q) => $q->where('assessor_id', $user->id))
                ->whereDoesntHave('reviews', fn ($q) => $q->where('assessor_id', $user->id)->where('status', 'submitted'))->count();
            if ($count) $items[] = ['title' => "$count submission menunggu penilaian Anda", 'url' => route('asesor.reviews.index')];
        } elseif ($user->role === 'peserta_assessment') {
            $count = SimulationReview::where('status', 'submitted')
                ->whereHas('session.participant', fn ($q) => $q->where('user_id', $user->id))->count();
            if ($count) $items[] = ['title' => "$count hasil penilaian tersedia", 'url' => route('peserta-assessment.results.index')];
        }
        return response()->json(['items' => $items])->header('Cache-Control', 'no-store');
    }
}
