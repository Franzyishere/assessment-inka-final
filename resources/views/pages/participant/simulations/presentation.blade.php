@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb :pageTitle="$submission ? 'Tampilan Presentasi' : 'Upload Presentasi'" />

@if($submission)
    <div class="space-y-6" x-data="{ isFullscreen: false, toggleFullscreen() { const el = document.getElementById('presentation-container'); if (!document.fullscreenElement) { el.requestFullscreen().then(() => this.isFullscreen = true).catch(() => window.open('{{ route('peserta-assessment.simulations.presentation.preview', $programSimulation) }}', '_blank')); } else { document.exitFullscreen().then(() => this.isFullscreen = false); } } }" @fullscreenchange.window="isFullscreen = !!document.fullscreenElement">
        <!-- Header & Action Card -->
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        Simulasi 4 · Presentasi
                    </span>
                    <span class="rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-400">
                        File Sudah Dikumpulkan
                    </span>
                </div>
                <h1 class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $programSimulation->scenario->type->name }}</h1>
                <p class="mt-1 text-xs text-gray-500">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $submission->original_filename }}</span> · {{ number_format($submission->file_size / 1024, 1) }} KB · Dikumpulkan pada {{ $submission->submitted_at?->timezone(config('assessment_access.timezone'))->format('d M Y, H:i') }} WIB
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('peserta-assessment.simulations.index') }}" class="crud-btn-secondary inline-flex items-center gap-1.5">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                    </svg>
                    <span>Daftar Simulasi</span>
                </a>
                <a href="{{ route('peserta-assessment.simulations.presentation.download', $programSimulation) }}" class="crud-btn-secondary inline-flex items-center gap-1.5" title="Unduh file PDF presentasi">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    <span>Unduh PDF</span>
                </a>
                <a target="_blank" href="{{ route('peserta-assessment.simulations.presentation.preview', $programSimulation) }}" class="crud-btn-secondary inline-flex items-center gap-1.5" title="Buka di Tab Baru">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/>
                    </svg>
                    <span>Tab Baru</span>
                </a>
                <button type="button" @click="toggleFullscreen()" class="crud-btn-primary inline-flex items-center gap-2">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                    </svg>
                    <span x-text="isFullscreen ? 'Keluar Fullscreen' : 'Mode Layar Penuh (Presentasi)'">Mode Layar Penuh (Presentasi)</span>
                </button>
            </div>
        </div>

        <!-- Presentation Guidance Card -->
        <div class="rounded-xl border border-brand-200 bg-brand-50/70 p-4 text-xs leading-relaxed text-brand-900 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300">
            <div class="flex items-start gap-3">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="18" height="14" x="3" y="3" rx="2"/><path d="M7 21h10M12 17v4"/>
                    </svg>
                </span>
                <div>
                    <h3 class="font-semibold text-brand-950 dark:text-white">Panduan Sesi Presentasi:</h3>
                    <p class="mt-0.5 text-brand-800 dark:text-brand-300">
                        File presentasi ini akan Anda tampilkan langsung kepada asesor selama sesi presentasi/wawancara. Gunakan tombol <strong>Mode Layar Penuh</strong> atau <strong>Buka di Tab Baru</strong> untuk menampilkan slide presentasi Anda secara jelas dan profesional.
                    </p>
                </div>
            </div>
        </div>

        <!-- Embedded Presentation PDF Viewer Container -->
        <div id="presentation-container" class="relative overflow-hidden rounded-2xl border border-gray-200 bg-gray-900 shadow-md transition-all dark:border-gray-800">
            <div class="flex items-center justify-between border-b border-gray-800 bg-gray-950/80 px-4 py-2 text-xs text-gray-400">
                <span class="font-mono text-gray-300">{{ $submission->original_filename }}</span>
                <span class="text-gray-500">Viewer Presentasi Peserta</span>
            </div>
            <iframe id="presentation-frame" title="Preview Presentasi Peserta" src="{{ route('peserta-assessment.simulations.presentation.preview', $programSimulation) }}#toolbar=1&navpanes=0" class="h-[80vh] min-h-[640px] w-full bg-white dark:bg-gray-900" allow="fullscreen"></iframe>
        </div>
    </div>
@else
    <div class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                Simulasi 4 · Presentasi
            </span>
        </div>
        <h1 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $programSimulation->scenario->type->name }}</h1>
        <p class="mt-2 text-sm leading-6 text-gray-500">Unggah file presentasi final yang nantinya akan Anda tampilkan dan presentasikan langsung kepada asesor.</p>

        <div class="mt-6 rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400">
            Format yang diterima hanya file <strong>PDF</strong>. Ukuran maksimal <strong>25 MB</strong>. Setelah dikumpulkan, file presentasi akan langsung tampil di halaman ini dan siap Anda gunakan untuk presentasi.
        </div>

        <form x-ref="presentationForm" method="POST" enctype="multipart/form-data" action="{{ route('peserta-assessment.simulations.presentation.submit', $programSimulation) }}" class="mt-6">
            @csrf
            <div>
                <label for="presentation" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih File Presentasi (PDF) *</label>
                <input id="presentation" type="file" name="presentation" accept="application/pdf,.pdf" required class="block w-full rounded-xl border border-gray-300 bg-transparent p-3 text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:border-gray-700 dark:text-gray-300">
                @error('presentation')<p class="mt-2 text-xs font-medium text-error-500">{{ $message }}</p>@enderror
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <a href="{{ route('peserta-assessment.simulations.show', $programSimulation) }}" class="crud-btn-secondary">Kembali</a>
                <button type="button" class="crud-btn-primary" @click="$dispatch('confirm-dialog', { title: 'Kumpulkan presentasi?', message: 'Pastikan file PDF sudah benar. File tidak dapat diganti setelah dikumpulkan.', confirmLabel: 'Ya, Kumpulkan', onConfirm: () => $refs.presentationForm.requestSubmit() })">Kumpulkan Presentasi</button>
            </div>
        </form>
    </div>
@endif
@endsection
