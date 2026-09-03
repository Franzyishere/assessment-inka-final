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
        $recruitmentRoles = [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_PESERTA_REKRUTMEN,
        ];

        return view('pages.portal.index', [
            'title' => 'Portal Utama',
            'assessmentAvailable' => $user->hasRole(...$assessmentRoles),
            'assessmentUrl' => $user->hasRole(...$assessmentRoles)
                ? route($user->dashboardRouteName())
                : null,
            'assessmentDescription' => match ($user->role) {
                User::ROLE_SUPER_ADMIN => 'Pantau seluruh aktivitas assessment, kelola pengguna, hak akses, dan audit sistem dari satu ruang kerja.',
                User::ROLE_ADMIN => 'Kelola program assessment, bank simulasi, peserta, penugasan asesor, dan monitoring pelaksanaan.',
                User::ROLE_ASESOR => 'Pantau peserta yang ditugaskan, buka hasil simulasi, berikan penilaian, dan susun rekomendasi.',
                User::ROLE_PESERTA_ASSESSMENT => 'Lihat jadwal, kerjakan simulasi yang tersedia, dan pantau hasil assessment Anda.',
                default => 'Modul assessment hanya tersedia bagi pengguna yang memiliki penugasan dan hak akses assessment.',
            },
            'recruitmentAvailable' => $user->hasRole(...$recruitmentRoles),
            'recruitmentUrl' => match ($user->role) {
                User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN => route('admin.recruitment.index'),
                User::ROLE_PESERTA_REKRUTMEN => route('peserta-rekrutmen.exams.index'),
                default => null,
            },
            'recruitmentDescription' => match ($user->role) {
                User::ROLE_SUPER_ADMIN => 'Kelola akses dan pantau aktivitas proses rekrutmen, termasuk master psikotes dan pelaksanaannya.',
                User::ROLE_ADMIN => 'Kelola kebutuhan rekrutmen, kandidat, tahapan seleksi, dan pelaksanaan psikotes dalam satu alur.',
                User::ROLE_PESERTA_REKRUTMEN => 'Pantau tahapan seleksi, jadwal, pengumuman, dan psikotes yang diberikan kepada Anda.',
                default => 'Proses kandidat dan tahapan psikotes dikelola secara terintegrasi di dalam modul rekrutmen.',
            },
        ]);
    }
}
