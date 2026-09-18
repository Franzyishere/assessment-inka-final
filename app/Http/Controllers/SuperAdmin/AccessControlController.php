<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function index(): View
    {
        $permissions = [
            'super_admin' => ['Dashboard sistem', 'Seluruh akun & role', 'Seluruh administrasi assessment dan undangan', 'Matriks hak akses', 'Audit log'],
            'admin' => ['Program assessment', 'Bank simulasi', 'Akun peserta', 'Undangan dan monitoring email', 'Penugasan asesor', 'Monitoring assessment'],
            'asesor' => ['Simulasi ditugaskan', 'Submission peserta', 'Aktivitas sesi', 'Penilaian & rekomendasi'],
            'peserta_assessment' => ['Login undangan dan OTP pada hari pelaksanaan', 'Simulasi Saya pada program yang diundang', 'Pengerjaan dan upload presentasi', 'Tanpa akses riwayat/hasil penilaian'],
        ];

        return view('pages.super-admin.roles.index', ['title' => 'Role & Hak Akses', 'permissions' => $permissions]);
    }
}
