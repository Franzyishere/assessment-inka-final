<?php

namespace App\Http\Controllers;

use App\Models\SimulationSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HeaderNotificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = [];
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            $count = SimulationSession::where('status', 'submitted')
                ->whereDoesntHave('archiveGrant')->count();
            if ($count) $items[] = ['title' => "$count hasil simulasi belum dibagikan kepada asesor", 'url' => route('admin.result-archives.index')];
        } elseif ($user->role === 'asesor') {
            $count = SimulationSession::where('status', 'submitted')
                ->whereHas('archiveGrant', fn ($q) => $q->where('assessor_id', $user->id))->count();
            if ($count) $items[] = ['title' => "$count arsip hasil assessment dibagikan kepada Anda", 'url' => route('asesor.reviews.index')];
        }
        return response()->json(['items' => $items])->header('Cache-Control', 'no-store');
    }
}
