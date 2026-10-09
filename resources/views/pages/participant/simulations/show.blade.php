@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Detail Simulasi" />
<div class="mx-auto max-w-4xl rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <span class="text-xs font-medium uppercase text-brand-500">{{ $programSimulation->scenario->type->name }}</span>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $programSimulation->scenario->type->delivery_mode === 'case_response' && $participation->requiresSimulationThreeChoice() ? 'Paket Simulasi 3 Belum Ditetapkan' : ($programSimulation->scenario->simulationThreePackageLabel() ?? $programSimulation->scenario->type->name) }}</h1>
    <p class="mt-3 text-sm leading-6 text-gray-500">{{ $programSimulation->scenario->description }}</p>
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><span class="text-xs text-gray-500">Durasi</span><p class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $programSimulation->scenario->duration_minutes }} menit</p></div>
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><span class="text-xs text-gray-500">Dibuka</span><p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $programSimulation->opens_at?->format('d M Y H:i') ?? 'Langsung' }}</p></div>
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><span class="text-xs text-gray-500">Status</span><p class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ str_replace('_', ' ', $session?->status ?? 'Belum dimulai') }}</p></div>
    </div>
    <div class="mt-6 flex items-start gap-4 rounded-xl border border-error-200 bg-error-50 p-4 shadow-theme-xs dark:border-error-500/30 dark:bg-error-500/10" role="alert">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-error-100 text-error-600 dark:bg-error-500/20 dark:text-error-400">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 3h.01M10.3 3.8 2.5 17.3A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.7L13.7 3.8a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
            <h2 class="text-sm font-semibold text-error-800 dark:text-error-300">Aktivitas pengerjaan dipantau sistem</h2>
            <p class="mt-1 text-sm leading-6 text-error-700 dark:text-error-400">Selama simulasi berlangsung, perpindahan tab atau keluar dari mode fullscreen akan terdeteksi dan tercatat sebagai aktivitas pengerjaan.</p>
        </div>
    </div>
    <div class="mt-6 rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/30 dark:bg-warning-500/10">
        <h2 class="text-sm font-semibold text-warning-800 dark:text-warning-400">Sebelum memulai assessment</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-warning-700 dark:text-warning-400"><li>Pastikan koneksi internet stabil dan perangkat memiliki daya yang cukup.</li><li>Siapkan waktu sesuai durasi karena timer berjalan setelah tombol mulai ditekan.</li><li>Mode fullscreen wajib digunakan selama pengerjaan.</li><li>Jawaban yang sudah dikumpulkan tidak dapat diubah kembali.</li></ul>
    </div>
    @if($participation->requiresSimulationThreeChoice() && $programSimulation->scenario->type->delivery_mode === 'case_response')
        <div class="mt-6 rounded-xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10"><h2 class="text-sm font-semibold text-warning-800 dark:text-warning-300">Menunggu penetapan paket Simulasi 3</h2><p class="mt-2 text-sm leading-6 text-warning-700 dark:text-warning-400">Admin akan menentukan CI materi panjang, CI materi pendek, atau In-Tray. Simulasi dapat dimulai setelah materi ditetapkan.</p></div>
    @endif
    @if (! $programSimulation->isAvailableForParticipant() && $session?->status !== 'submitted' && $session?->status !== 'in_progress')
        <div class="mt-6 flex items-start gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-theme-xs">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-amber-900">Sesi simulasi belum dibuka oleh Admin</h2>
                <p class="mt-1 text-sm leading-6 text-amber-800">Tombol mulai akan aktif secara otomatis setelah admin/pengawas assessment membuka sesi ini. Harap tunggu instruksi di ruang assessment sebelum memulai pengerjaan.</p>
            </div>
        </div>
    @endif
    <div class="mt-7 flex justify-end gap-3">
        <a href="{{ route('peserta-assessment.simulations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Kembali</a>
        @if ($session?->status === 'in_progress')
            <a href="{{ match($programSimulation->scenario->type->delivery_mode) { 'file_upload' => route('peserta-assessment.simulations.presentation', $programSimulation), 'assessor_observation' => route('peserta-assessment.simulations.lgd-review', $programSimulation), 'case_response' => $programSimulation->scenario->materialPages->isNotEmpty() ? route('peserta-assessment.simulations.material', [$programSimulation, 1]) : route('peserta-assessment.simulations.case-response', $programSimulation), default => route('peserta-assessment.simulations.material', [$programSimulation, 1]) } }}" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Lanjutkan</a>
        @elseif ($session?->status === 'submitted')
            @if ($programSimulation->scenario->type->delivery_mode === 'file_upload')
                <a href="{{ route('peserta-assessment.simulations.presentation', $programSimulation) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600 shadow-xs">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="18" height="14" x="3" y="3" rx="2"/><path d="M7 21h10M12 17v4"/>
                    </svg>
                    <span>Tampilkan Presentasi</span>
                </a>
            @else
                <span class="rounded-lg bg-success-50 px-5 py-2.5 text-sm font-medium text-success-700">Sudah dikumpulkan</span>
            @endif
        @elseif ($participation->requiresSimulationThreeChoice() && $programSimulation->scenario->type->delivery_mode === 'case_response')
            <span class="rounded-lg bg-warning-50 px-5 py-2.5 text-sm font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Menunggu penetapan admin</span>
        @elseif (! $programSimulation->isAvailableForParticipant())
            <button disabled type="button" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-100 px-5 py-2.5 text-sm font-medium text-gray-400 cursor-not-allowed shadow-none" title="Simulasi ini belum dibuka oleh Admin">
                <svg class="size-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span>Menunggu Instruksi Admin</span>
            </button>
        @elseif (in_array($programSimulation->scenario->type->delivery_mode, ['multi_page_response', 'file_upload', 'case_response'], true))
            <form method="POST" action="{{ route('peserta-assessment.simulations.start', $programSimulation) }}">@csrf<button class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Mulai Simulasi</button></form>
        @elseif ($programSimulation->scenario->type->delivery_mode === 'assessor_observation')
            <form method="POST" action="{{ route('peserta-assessment.simulations.start', $programSimulation) }}">@csrf<button class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Mulai Simulasi 2</button></form>
        @else
            <span class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-medium text-gray-500 dark:bg-white/10">Dilaksanakan bersama asesor</span>
        @endif
    </div>
</div>
@endsection
