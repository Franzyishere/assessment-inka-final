<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function index(): View
    {
        $permissions = [
            'super_admin' => ['Dashboard sistem', 'Seluruh akun & role', 'Matriks hak akses', 'Audit log'],
            'admin' => ['Program assessment', 'Bank simulasi', 'Akun peserta', 'Penugasan asesor', 'Monitoring assessment'],
            'asesor' => ['Simulasi ditugaskan', 'Submission peserta', 'Aktivitas sesi', 'Penilaian & rekomendasi'],
            'peserta_assessment' => ['Program sendiri', 'Simulasi sendiri', 'Upload presentasi', 'Hasil assessment'],
            'peserta_rekrutmen' => ['Portal rekrutmen (placeholder)'],
        ];

        return view('pages.super-admin.roles.index', ['title' => 'Role & Hak Akses', 'permissions' => $permissions]);
    }
}
