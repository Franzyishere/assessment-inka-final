<?php

namespace App\Http\Controllers;

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessorAssignment;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRouteName());
    }

    public function superAdmin(): View
    {
        return $this->dashboard('Dashboard Sistem', 'Kelola akses dan pantau fondasi sistem Assessment INKA.', [
            ['label' => 'Pengguna Aktif', 'value' => '5 role', 'description' => 'Akses dipisahkan berdasarkan tanggung jawab'],
            ['label' => 'Keamanan', 'value' => 'Aktif', 'description' => 'Login dibatasi dan sesi diregenerasi'],
            ['label' => 'Audit Sistem', 'value' => 'Fondasi', 'description' => 'Modul audit lengkap dibangun pada tahap berikutnya'],
        ]);
    }

    public function admin(): View
    {
        $programCount = AssessmentProgram::count();
        $activePrograms = AssessmentProgram::where('status', 'active')->count();
        $scenarioCount = SimulationScenario::whereIn('status', ['active', 'published'])->count();
        $submittedCount = SimulationSession::where('status', 'submitted')->count();

        return $this->dashboard('Dashboard Admin HCGA', 'Kelola program assessment, simulasi, peserta, dan penugasan asesor.', [
            ['label' => 'Program Assessment', 'value' => (string) $programCount, 'description' => $activePrograms.' program sedang aktif'],
            ['label' => 'Bank Simulasi', 'value' => (string) $scenarioCount, 'description' => 'Skenario berstatus aktif'],
            ['label' => 'Submission Peserta', 'value' => (string) $submittedCount, 'description' => 'Simulasi yang sudah dikumpulkan'],
        ]);
    }

    public function asesor(Request $request): View
    {
        $assignments = AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->with('programSimulation.sessions')
            ->get();
        $sessions = $assignments->pluck('programSimulation.sessions')->flatten();

        return $this->dashboard('Dashboard Asesor', 'Pantau peserta dan nilai simulasi yang ditugaskan kepada Anda.', [
            ['label' => 'Simulasi Ditugaskan', 'value' => (string) $assignments->count(), 'description' => 'Penugasan aktif dari Admin HCGA'],
            ['label' => 'Peserta Dipantau', 'value' => (string) $sessions->pluck('assessment_participant_id')->unique()->count(), 'description' => 'Peserta yang sudah memulai simulasi'],
            ['label' => 'Menunggu Penilaian', 'value' => (string) $sessions->where('status', 'submitted')->count(), 'description' => 'Termasuk file presentasi peserta'],
        ]);
    }

    public function pesertaAssessment(Request $request): View
    {
        $participations = AssessmentParticipant::query()
            ->where('user_id', $request->user()->id)
            ->with(['program.simulations', 'sessions'])
            ->get();
        $simulationCount = $participations->sum(fn ($item) => $item->program->simulations->count());
        $submittedCount = $participations->sum(fn ($item) => $item->sessions->where('status', 'submitted')->count());
        $nextSchedule = $participations->pluck('program.simulations')->flatten()
            ->pluck('opens_at')->filter(fn ($date) => $date?->isFuture())->sort()->first();

        return $this->dashboard('Dashboard Peserta Assessment', 'Lihat jadwal dan simulasi assessment internal yang ditugaskan.', [
            ['label' => 'Simulasi Ditugaskan', 'value' => (string) $simulationCount, 'description' => $submittedCount.' simulasi sudah dikumpulkan'],
            ['label' => 'Jadwal Berikutnya', 'value' => $nextSchedule?->format('d M H:i') ?? '-', 'description' => $nextSchedule ? 'Waktu simulasi terdekat' : 'Tidak ada jadwal mendatang'],
            ['label' => 'Status', 'value' => $participations->isEmpty() ? 'Menunggu' : 'Ditugaskan', 'description' => $participations->isEmpty() ? 'Menunggu penugasan program assessment' : $participations->count().' program assessment aktif'],
        ]);
    }

    public function pesertaRekrutmen(): View
    {
        return $this->dashboard('Dashboard Peserta Rekrutmen', 'Fondasi portal rekrutmen telah tersedia dan detail alurnya menunggu keputusan client.', [
            ['label' => 'Ujian Aktif', 'value' => '0', 'description' => 'Belum ada ujian yang dijadwalkan'],
            ['label' => 'Tahap Seleksi', 'value' => '-', 'description' => 'Akan disesuaikan dengan proses rekrutmen final'],
            ['label' => 'Status', 'value' => 'Menunggu', 'description' => 'Belum ada proses rekrutmen aktif'],
        ]);
    }

    public function placeholder(Request $request): View
    {
        $title = (string) $request->route()->getDefaults()['pageTitle'];

        return view('pages.placeholder', [
            'title' => $title,
            'description' => 'Struktur menu dan proteksi akses sudah tersedia. Fitur ini akan dibangun pada tahap domain berikutnya.',
        ]);
    }

    private function dashboard(string $title, string $description, array $metrics): View
    {
        return view('pages.dashboard.index', compact('title', 'description', 'metrics'));
    }
}
