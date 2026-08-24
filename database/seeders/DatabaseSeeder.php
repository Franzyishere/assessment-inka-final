<?php

namespace Database\Seeders;

use App\Models\SimulationType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Super Administrator', 'email' => 'superadmin@inka.co.id', 'role' => User::ROLE_SUPER_ADMIN],
            ['name' => 'Administrator HCGA', 'email' => 'admin@inka.co.id', 'role' => User::ROLE_ADMIN],
            ['name' => 'Asesor INKA', 'email' => 'asesor@inka.co.id', 'role' => User::ROLE_ASESOR],
            ['name' => 'Peserta Assessment', 'email' => 'peserta.assessment@inka.co.id', 'role' => User::ROLE_PESERTA_ASSESSMENT],
            ['name' => 'Peserta Rekrutmen', 'email' => 'peserta.rekrutmen@inka.co.id', 'role' => User::ROLE_PESERTA_REKRUTMEN],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [...$user, 'password' => Hash::make('password')],
            );
        }

        $simulationTypes = [
            [
                'code' => SimulationType::PROBLEM_ANALYSIS,
                'name' => 'Simulasi 1 - Problem Analysis',
                'delivery_mode' => 'multi_page_response',
                'sequence' => 1,
                'description' => 'Peserta mempelajari materi multi-halaman dan memberikan respons analisis.',
            ],
            [
                'code' => SimulationType::LGD,
                'name' => 'Simulasi 2 - Leaderless Group Discussion',
                'delivery_mode' => 'assessor_observation',
                'sequence' => 2,
                'description' => 'Pelaksanaan diskusi kelompok dan observasi kompetensi oleh asesor.',
            ],
            [
                'code' => SimulationType::CRITICAL_INCIDENT,
                'name' => 'Simulasi 3 - Critical Incident / In-Tray',
                'delivery_mode' => 'case_response',
                'sequence' => 3,
                'description' => 'Peserta menangani kasus kritis dan menentukan prioritas keputusan.',
            ],
            [
                'code' => SimulationType::PRESENTATION,
                'name' => 'Simulasi 4 - Presentasi Peserta',
                'delivery_mode' => 'file_upload',
                'sequence' => 4,
                'description' => 'Peserta mengunggah file presentasi untuk ditampilkan dan dinilai asesor.',
            ],
        ];

        foreach ($simulationTypes as $simulationType) {
            SimulationType::updateOrCreate(
                ['code' => $simulationType['code']],
                [...$simulationType, 'is_active' => true],
            );
        }
    }
}
