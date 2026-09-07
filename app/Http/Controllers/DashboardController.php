<?php

namespace App\Http\Controllers;

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessorAssignment;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationReview;
use App\Models\User;
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
        $roles = User::query()->selectRaw('role, count(*) as total')->groupBy('role')->orderBy('role')->get();
        $programStatuses = AssessmentProgram::query()->selectRaw('status, count(*) as total')->groupBy('status')->get();

        return $this->dashboard('Dashboard Sistem', 'Kelola akses dan pantau fondasi sistem Assessment INKA.', [
            ['label' => 'Role Pengguna', 'value' => $roles->count().' role', 'description' => 'Akses dipisahkan berdasarkan tanggung jawab'],
            ['label' => 'Keamanan', 'value' => 'Aktif', 'description' => 'Login dibatasi dan sesi diregenerasi'],
            ['label' => 'Audit Sistem', 'value' => 'Tercatat', 'description' => 'Aktivitas penting pengguna tersimpan dalam audit log'],
        ], [
            $this->barChart('Distribusi Pengguna', 'Jumlah akun pada setiap role', $roles->pluck('role')->map(fn ($role) => str($role)->replace('_', ' ')->title())->all(), $roles->pluck('total')->all(), 'Pengguna'),
            $this->donutChart('Status Program Assessment', 'Ringkasan seluruh program berdasarkan status', $programStatuses->pluck('status')->map(fn ($status) => str($status)->title())->all(), $programStatuses->pluck('total')->all()),
        ]);
    }

    public function admin(): View
    {
        $programCount = AssessmentProgram::count();
        $activePrograms = AssessmentProgram::where('status', 'active')->count();
        $scenarioCount = SimulationScenario::whereIn('status', ['active', 'published'])->count();
        $submittedCount = SimulationSession::where('status', 'submitted')->count();

        $programs = AssessmentProgram::query()->withCount('participants')->latest()->limit(6)->get()->reverse()->values();
        $sessionStatuses = SimulationSession::query()->selectRaw('status, count(*) as total')->groupBy('status')->get();

        return $this->dashboard('Dashboard Admin HCGA', 'Kelola program assessment, simulasi, peserta, dan penugasan asesor.', [
            ['label' => 'Program Assessment', 'value' => (string) $programCount, 'description' => $activePrograms.' program sedang aktif'],
            ['label' => 'Bank Simulasi', 'value' => (string) $scenarioCount, 'description' => 'Skenario berstatus aktif'],
            ['label' => 'Submission Peserta', 'value' => (string) $submittedCount, 'description' => 'Simulasi yang sudah dikumpulkan'],
        ], [
            $this->barChart('Peserta per Program', 'Enam program assessment terbaru', $programs->pluck('name')->all(), $programs->pluck('participants_count')->all(), 'Peserta'),
            $this->donutChart('Status Pengerjaan', 'Posisi pengerjaan seluruh simulasi peserta', $sessionStatuses->pluck('status')->map(fn ($status) => $this->statusLabel($status))->all(), $sessionStatuses->pluck('total')->all()),
        ]);
    }

    public function asesor(Request $request): View
    {
        $assignments = AssessorAssignment::query()
            ->where('assessor_id', $request->user()->id)
            ->with(['programSimulation.program:id,name', 'programSimulation.sessions:id,assessment_program_simulation_id,assessment_participant_id,status'])
            ->get();
        $sessions = $assignments->pluck('programSimulation.sessions')->flatten();
        $programGroups = $assignments->groupBy(fn ($assignment) => $assignment->programSimulation->program?->name ?? 'Program');
        $reviewedSessionIds = SimulationReview::query()->where('assessor_id', $request->user()->id)->where('status', 'submitted')->pluck('simulation_session_id');
        $submittedSessions = $sessions->where('status', 'submitted');
        $waitingReviewCount = $submittedSessions->whereNotIn('id', $reviewedSessionIds)->count();

        return $this->dashboard('Dashboard Asesor', 'Pantau peserta dan nilai simulasi yang ditugaskan kepada Anda.', [
            ['label' => 'Simulasi Ditugaskan', 'value' => (string) $assignments->count(), 'description' => 'Penugasan aktif dari Admin HCGA'],
            ['label' => 'Peserta Dipantau', 'value' => (string) $sessions->pluck('assessment_participant_id')->unique()->count(), 'description' => 'Peserta yang sudah memulai simulasi'],
            ['label' => 'Menunggu Penilaian', 'value' => (string) $waitingReviewCount, 'description' => 'Submission yang belum Anda finalisasi'],
        ], [
            $this->barChart('Submission per Program', 'Jawaban peserta yang telah dikumpulkan', $programGroups->keys()->all(), $programGroups->map(fn ($items) => $items->pluck('programSimulation.sessions')->flatten()->where('status', 'submitted')->count())->values()->all(), 'Submission'),
            $this->donutChart('Progres Penilaian', 'Status penilaian submission yang masuk', ['Sudah Dinilai', 'Menunggu Penilaian'], [$submittedSessions->whereIn('id', $reviewedSessionIds)->count(), $waitingReviewCount]),
        ]);
    }

    public function pesertaAssessment(Request $request): View
    {
        $participations = AssessmentParticipant::query()
            ->where('user_id', $request->user()->id)
            ->with(['program:id,name', 'program.simulations:id,assessment_program_id,opens_at'])
            ->withCount(['sessions as submitted_count' => fn ($query) => $query->where('status', 'submitted')])
            ->get();
        $simulationCount = $participations->sum(fn ($item) => $item->program->simulations->count());
        $submittedCount = $participations->sum('submitted_count');
        $nextSchedule = $participations->pluck('program.simulations')->flatten()
            ->pluck('opens_at')->filter(fn ($date) => $date?->isFuture())->sort()->first();

        return $this->dashboard('Dashboard Peserta Assessment', 'Lihat jadwal dan simulasi assessment internal yang ditugaskan.', [
            ['label' => 'Simulasi Ditugaskan', 'value' => (string) $simulationCount, 'description' => $submittedCount.' simulasi sudah dikumpulkan'],
            ['label' => 'Jadwal Berikutnya', 'value' => $nextSchedule?->format('d M H:i') ?? '-', 'description' => $nextSchedule ? 'Waktu simulasi terdekat' : 'Tidak ada jadwal mendatang'],
            ['label' => 'Status', 'value' => $participations->isEmpty() ? 'Menunggu' : 'Ditugaskan', 'description' => $participations->isEmpty() ? 'Menunggu penugasan program assessment' : $participations->count().' program assessment aktif'],
        ], [
            $this->barChart('Progres per Program', 'Perbandingan simulasi selesai dan total simulasi', $participations->pluck('program.name')->all(), $participations->pluck('submitted_count')->all(), 'Selesai', $participations->map(fn ($item) => $item->program->simulations->count())->all()),
            $this->donutChart('Progres Keseluruhan', 'Ringkasan simulasi yang ditugaskan kepada Anda', ['Selesai', 'Belum Selesai'], [$submittedCount, max(0, $simulationCount - $submittedCount)]),
        ]);
    }

    private function dashboard(string $title, string $description, array $metrics, array $charts = []): View
    {
        return view('pages.dashboard.index', compact('title', 'description', 'metrics', 'charts'));
    }

    private function barChart(string $title, string $description, array $labels, array $values, string $seriesName, ?array $totals = null): array
    {
        $series = [['name' => $seriesName, 'data' => array_map('intval', array_values($values))]];
        if ($totals !== null) {
            $series[] = ['name' => 'Total', 'data' => array_map('intval', array_values($totals))];
        }

        return compact('title', 'description', 'labels', 'series') + ['type' => 'bar'];
    }

    private function donutChart(string $title, string $description, array $labels, array $values): array
    {
        return compact('title', 'description', 'labels') + ['type' => 'donut', 'series' => array_map('intval', array_values($values))];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'not_started' => 'Belum Dimulai',
            'in_progress' => 'Sedang Dikerjakan',
            'submitted' => 'Selesai',
            default => str($status)->replace('_', ' ')->title()->toString(),
        };
    }
}
