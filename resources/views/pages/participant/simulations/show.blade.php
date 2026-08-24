@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Detail Simulasi" />
@if (session('success'))<div class="mx-auto mb-5 max-w-4xl rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>@endif
<div class="mx-auto max-w-4xl rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <span class="text-xs font-medium uppercase text-brand-500">{{ $programSimulation->scenario->type->name }}</span>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $participation->requiresSimulationThreeChoice() ? 'Pilih Jalur Simulasi 3' : ($programSimulation->scenario->simulationThreePackageLabel() ?? $programSimulation->scenario->type->name) }}</h1>
    <p class="mt-3 text-sm leading-6 text-gray-500">{{ $programSimulation->scenario->description }}</p>
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><span class="text-xs text-gray-500">Durasi</span><p class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $programSimulation->scenario->duration_minutes }} menit</p></div>
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><span class="text-xs text-gray-500">Dibuka</span><p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $programSimulation->opens_at?->format('d M Y H:i') ?? 'Langsung' }}</p></div>
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><span class="text-xs text-gray-500">Status</span><p class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ str_replace('_', ' ', $session?->status ?? 'Belum dimulai') }}</p></div>
    </div>
    <div class="mt-6 rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/30 dark:bg-warning-500/10">
        <h2 class="text-sm font-semibold text-warning-800 dark:text-warning-400">Sebelum memulai assessment</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-warning-700 dark:text-warning-400"><li>Pastikan koneksi internet stabil dan perangkat memiliki daya yang cukup.</li><li>Siapkan waktu sesuai durasi karena timer berjalan setelah tombol mulai ditekan.</li><li>Mode fullscreen wajib digunakan selama pengerjaan.</li><li>Jawaban yang sudah dikumpulkan tidak dapat diubah kembali.</li></ul>
    </div>
    @if($participation->requiresSimulationThreeChoice() && $programSimulation->scenario->type->delivery_mode === 'case_response')
        <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50/60 p-5 dark:border-brand-500/30 dark:bg-brand-500/10" x-data="{ selected: '{{ old('simulation_package') }}', confirmed: {{ old('confirmation') ? 'true' : 'false' }}, modal: false }">
            <div class="flex items-start gap-3"><span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">3</span><div><h2 class="font-semibold text-gray-800 dark:text-white/90">Pilih jalur Simulasi 3</h2><p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Khusus peserta Spesialis Madya. Pilih satu materi yang akan dikerjakan. Pilihan bersifat final setelah dikonfirmasi.</p></div></div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach(['ci_3' => 'Critical Incident 3', 'in_tray_3' => 'In-Tray 3'] as $package => $label)
                    <button type="button" @click="selected = '{{ $package }}'" :class="selected === '{{ $package }}' ? 'border-brand-500 bg-white ring-2 ring-brand-100 dark:bg-gray-900' : 'border-gray-200 bg-white/70 dark:border-gray-700 dark:bg-gray-900/50'" class="rounded-xl border p-4 text-left transition"><span class="font-medium text-gray-800 dark:text-white/90">{{ $label }}</span><span class="mt-1 block text-xs text-gray-500">{{ $package === 'ci_3' ? 'Paket Critical Incident untuk Madya' : 'Paket In-Tray untuk Madya' }}</span></button>
                @endforeach
            </div>
            @error('simulation_package')<p class="mt-2 text-sm text-error-500">{{ $message }}</p>@enderror
            @error('confirmation')<p class="mt-2 text-sm text-error-500">{{ $message }}</p>@enderror
            <div class="mt-4 flex justify-end"><button type="button" @click="if (selected) modal = true" :disabled="!selected" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">Konfirmasi Pilihan</button></div>
            <div x-show="modal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="modal = false"></div>
                <form method="POST" action="{{ route('peserta-assessment.simulations.choose-simulation-three', $programSimulation) }}" class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">@csrf
                    <input type="hidden" name="simulation_package" :value="selected">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Konfirmasi pilihan final</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-500">Anda memilih <strong class="text-gray-800 dark:text-white" x-text="selected === 'ci_3' ? 'Critical Incident 3' : 'In-Tray 3'"></strong>. Setelah disimpan, pilihan tidak dapat diganti dan paket lainnya tidak dapat dibuka.</p>
                    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-warning-200 bg-warning-50 p-3 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300"><input type="checkbox" name="confirmation" value="1" x-model="confirmed" class="mt-0.5 size-4 rounded border-gray-300"><span>Saya memahami dan menyetujui bahwa pilihan ini bersifat final.</span></label>
                    <div class="mt-6 flex justify-end gap-3"><button type="button" @click="modal = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Periksa kembali</button><button type="submit" :disabled="!confirmed" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">Simpan & Kunci Pilihan</button></div>
                </form>
            </div>
        </div>
    @endif
    <div class="mt-7 flex justify-end gap-3">
        <a href="{{ route('peserta-assessment.simulations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Kembali</a>
        @if ($session?->status === 'in_progress')
            <a href="{{ match($programSimulation->scenario->type->delivery_mode) { 'file_upload' => route('peserta-assessment.simulations.presentation', $programSimulation), 'assessor_observation' => route('peserta-assessment.simulations.lgd-review', $programSimulation), 'case_response' => $programSimulation->scenario->materialPages->isNotEmpty() ? route('peserta-assessment.simulations.material', [$programSimulation, 1]) : route('peserta-assessment.simulations.case-response', $programSimulation), default => route('peserta-assessment.simulations.material', [$programSimulation, 1]) } }}" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Lanjutkan</a>
        @elseif ($session?->status === 'submitted')
            <span class="rounded-lg bg-success-50 px-5 py-2.5 text-sm font-medium text-success-700">Sudah dikumpulkan</span>
        @elseif ($participation->requiresSimulationThreeChoice() && $programSimulation->scenario->type->delivery_mode === 'case_response')
            <span class="rounded-lg bg-warning-50 px-5 py-2.5 text-sm font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Pilih jalur terlebih dahulu</span>
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
