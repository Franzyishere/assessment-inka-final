@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Detail Simulasi" />
<div class="mx-auto max-w-4xl rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <span class="text-xs font-medium uppercase text-brand-500">{{ $programSimulation->scenario->type->name }}</span>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $participation->requiresSimulationThreeChoice() ? 'Paket Simulasi 3 Belum Ditetapkan' : ($programSimulation->scenario->simulationThreePackageLabel() ?? $programSimulation->scenario->type->name) }}</h1>
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
        <div class="mt-6 rounded-xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10"><h2 class="text-sm font-semibold text-warning-800 dark:text-warning-300">Menunggu penetapan paket Simulasi 3</h2><p class="mt-2 text-sm leading-6 text-warning-700 dark:text-warning-400">Asesor akan menentukan apakah Anda mengerjakan Critical Incident 3 atau In-Tray 3. Simulasi dapat dimulai setelah paket ditetapkan.</p></div>
    @endif
    <div class="mt-7 flex justify-end gap-3">
        <a href="{{ route('peserta-assessment.simulations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Kembali</a>
        @if ($session?->status === 'in_progress')
            <a href="{{ match($programSimulation->scenario->type->delivery_mode) { 'file_upload' => route('peserta-assessment.simulations.presentation', $programSimulation), 'assessor_observation' => route('peserta-assessment.simulations.lgd-review', $programSimulation), 'case_response' => $programSimulation->scenario->materialPages->isNotEmpty() ? route('peserta-assessment.simulations.material', [$programSimulation, 1]) : route('peserta-assessment.simulations.case-response', $programSimulation), default => route('peserta-assessment.simulations.material', [$programSimulation, 1]) } }}" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Lanjutkan</a>
        @elseif ($session?->status === 'submitted')
            <span class="rounded-lg bg-success-50 px-5 py-2.5 text-sm font-medium text-success-700">Sudah dikumpulkan</span>
        @elseif ($participation->requiresSimulationThreeChoice() && $programSimulation->scenario->type->delivery_mode === 'case_response')
            <span class="rounded-lg bg-warning-50 px-5 py-2.5 text-sm font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Menunggu penetapan asesor</span>
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
