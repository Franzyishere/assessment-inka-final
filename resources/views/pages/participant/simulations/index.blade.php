@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Simulasi Saya" />

<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs"><x-common.search-form :action="route('peserta-assessment.simulations.index')" placeholder="Cari program atau simulasi..." /></div>

<div class="space-y-6">
    @forelse ($participations as $participation)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <header class="flex flex-col gap-3 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Program Assessment</p><h2 class="mt-1 font-semibold text-gray-900">{{ $participation->program->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $participation->categoryLabel() }}</p></div>
                <div class="w-fit rounded-xl border border-gray-200 bg-white px-4 py-2.5 shadow-theme-xs"><p class="text-xs text-gray-500">Jadwal program</p><p class="mt-0.5 text-sm font-semibold text-gray-800">{{ $participation->program->starts_at?->format('d M Y, H:i') ?? 'Belum ditentukan' }}</p></div>
            </header>

            <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
                @forelse ($participation->program->simulations as $simulation)
                    @php
                        $session = $participation->sessions->firstWhere('assessment_program_simulation_id', $simulation->id);
                        $status = $session?->status ?? 'not_started';
                        $statusLabel = match($status) { 'submitted' => 'Selesai', 'in_progress' => 'Sedang Dikerjakan', default => 'Belum Dimulai' };
                        $statusClass = match($status) { 'submitted' => 'bg-success-50 text-success-700', 'in_progress' => 'bg-warning-50 text-warning-700', default => 'bg-gray-100 text-gray-600' };
                    @endphp
                    <article class="rounded-2xl border border-gray-200 p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-theme-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Simulasi {{ $simulation->scenario->type->sequence }}</span><h3 class="mt-1 font-semibold text-gray-900">{{ $simulation->scenario->type->name }}</h3>@if($simulation->scenario->simulationThreePackageLabel())<p class="mt-1 text-xs leading-5 text-gray-500">{{ $participation->requiresSimulationThreeChoice() ? 'Menunggu penetapan paket CI 3 atau In-Tray 3 oleh asesor.' : $simulation->scenario->simulationThreePackageLabel() }}</p>@endif</div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="mt-5 flex items-center gap-2 border-t border-gray-100 pt-4 text-sm text-gray-500"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>{{ $simulation->scenario->duration_minutes }} menit</span></div>
                        <div class="mt-4 flex justify-end"><a href="{{ route('peserta-assessment.simulations.show', $simulation) }}" class="{{ $status === 'submitted' ? 'crud-btn-secondary' : 'crud-btn-primary' }}">{{ $status === 'in_progress' ? 'Lanjutkan' : 'Lihat Detail' }} <span aria-hidden="true">→</span></a></div>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-success-200 bg-success-50 px-4 py-10 text-center"><h3 class="font-medium text-success-800">Tidak ada simulasi aktif</h3><p class="mt-1 text-sm text-success-700">Semua simulasi telah selesai atau melewati jadwal dan otomatis disembunyikan.</p></div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center"><div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 7V3m8 4V3M4 11h16M6 5h12a2 2 0 0 1 2-2v12H4V7a2 2 0 0 1 2-2Z"/></svg></div><h2 class="mt-4 font-semibold text-gray-800">Belum ada program assessment</h2><p class="mt-2 text-sm text-gray-500">Program akan muncul setelah Anda ditugaskan oleh Admin HCGA.</p></div>
    @endforelse
</div>
@endsection
