@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Jadwal Assessment" />

<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs"><x-common.search-form :action="route('peserta-assessment.schedule.index')" placeholder="Cari program atau simulasi..." /></div>

@php
    $allSimulations = $participations->pluck('program.simulations')->flatten();
    $upcomingCount = $allSimulations->filter(fn ($simulation) => $simulation->opens_at?->isFuture())->count();
    $availableCount = $allSimulations->filter(fn ($simulation) => (! $simulation->opens_at || $simulation->opens_at->isPast()) && (! $simulation->closes_at || $simulation->closes_at->isFuture()))->count();
@endphp

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Program ditugaskan</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ $participations->count() }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Simulasi tersedia</p><p class="mt-2 text-3xl font-semibold text-success-600">{{ $availableCount }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Jadwal mendatang</p><p class="mt-2 text-3xl font-semibold text-brand-600">{{ $upcomingCount }}</p></div>
</div>

<div class="space-y-6">
    @forelse($participations as $participation)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <header class="flex flex-col gap-3 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Program Assessment</p><h2 class="mt-1 font-semibold text-gray-900">{{ $participation->program->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $participation->categoryLabel() }}</p></div>
                <div class="text-left sm:text-right"><p class="text-xs text-gray-500">Periode program</p><p class="mt-1 text-sm font-semibold text-gray-800">{{ $participation->program->starts_at?->format('d M Y, H:i') ?? 'Belum ditentukan' }} — {{ $participation->program->ends_at?->format('d M Y, H:i') ?? 'Selesai tanpa batas waktu' }}</p></div>
            </header>

            <div class="divide-y divide-gray-100">
                @forelse($participation->program->simulations as $simulation)
                    @php
                        $session = $participation->sessions->firstWhere('assessment_program_simulation_id', $simulation->id);
                        $isClosed = ($simulation->closes_at?->isPast() ?? false) || ($participation->program->ends_at?->isPast() ?? false);
                        $isUpcoming = $simulation->opens_at?->isFuture() ?? false;
                        [$statusLabel, $statusClass] = match (true) {
                            $session?->status === 'submitted' => ['Selesai', 'bg-success-50 text-success-700'],
                            $session?->status === 'in_progress' => ['Sedang Dikerjakan', 'bg-warning-50 text-warning-700'],
                            $isClosed => ['Jadwal Berakhir', 'bg-gray-100 text-gray-600'],
                            $isUpcoming => ['Akan Datang', 'bg-brand-50 text-brand-700'],
                            default => ['Tersedia', 'bg-success-50 text-success-700'],
                        };
                    @endphp
                    <article class="flex flex-col gap-4 px-5 py-5 transition hover:bg-gray-50/70 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex min-w-0 items-start gap-4">
                            <div class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-700"><span class="text-[10px] font-semibold uppercase">Sim</span><span class="text-lg font-bold leading-none">{{ $simulation->scenario->type->sequence }}</span></div>
                            <div class="min-w-0"><h3 class="font-semibold text-gray-900">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</h3><p class="mt-1 text-sm text-gray-500">Durasi {{ $simulation->scenario->duration_minutes }} menit</p>@if($participation->requiresSimulationThreeChoice() && $simulation->scenario->type->delivery_mode === 'case_response')<p class="mt-1 text-xs text-warning-700">Menunggu penetapan materi Simulasi 3 oleh admin.</p>@endif</div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:min-w-[540px] lg:items-center">
                            <div><p class="text-xs text-gray-500">Mulai</p><p class="mt-1 text-sm font-medium text-gray-800">{{ $simulation->opens_at?->format('d M Y, H:i') ?? 'Langsung tersedia' }}</p></div>
                            <div><p class="text-xs text-gray-500">Berakhir</p><p class="mt-1 text-sm font-medium text-gray-800">{{ $simulation->closes_at?->format('d M Y, H:i') ?? 'Mengikuti program' }}</p></div>
                            <div class="flex items-center justify-between gap-3 sm:justify-end"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>@if(! $isClosed && ! $isUpcoming && $session?->status !== 'submitted')<a href="{{ route('peserta-assessment.simulations.show', $simulation) }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Buka →</a>@endif</div>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center"><h3 class="font-medium text-gray-800">Belum ada jadwal simulasi</h3><p class="mt-1 text-sm text-gray-500">Jadwal akan muncul setelah Admin HCGA menyiapkan simulasi program.</p></div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center"><div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 7V3m8 4V3M4 11h16M6 5h12a2 2 0 0 1 2-2v12H4V7a2 2 0 0 1 2-2Z"/></svg></div><h2 class="mt-4 font-semibold text-gray-800">Belum ada jadwal assessment</h2><p class="mt-2 text-sm text-gray-500">Jadwal akan tersedia setelah Anda ditugaskan ke program assessment.</p></div>
    @endforelse
</div>
@endsection
