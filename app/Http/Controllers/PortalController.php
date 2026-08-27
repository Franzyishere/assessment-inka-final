<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $assessmentRoles = [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_ASESOR,
            User::ROLE_PESERTA_ASSESSMENT,
        ];

        return view('pages.portal.index', [
            'title' => 'Portal Utama',
            'assessmentAvailable' => $user->hasRole(...$assessmentRoles),
            'assessmentUrl' => $user->hasRole(...$assessmentRoles)
                ? route($user->dashboardRouteName())
                : null,
        ]);
    }
}
