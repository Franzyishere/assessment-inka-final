@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Simulasi Saya" />

<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs"><x-common.search-form :action="route('peserta-assessment.simulations.index')" placeholder="Cari program atau simulasi..." /></div>

<div class="space-y-6">
    @forelse ($participations as $participation)
        <section class="overflow-hidden rounded-3xl border border-brand-200/80 bg-gradient-to-b from-brand-50/40 via-white to-gray-50/60 shadow-theme-xs">
            <header class="relative flex flex-col gap-3 overflow-hidden bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="pointer-events-none absolute -right-10 -top-16 size-40 rounded-full bg-white/10"></div>
                <div class="relative z-10"><p class="text-xs font-semibold uppercase tracking-wider text-white/80">Program Assessment</p><h2 class="mt-1 text-lg font-bold text-white">{{ $participation->program->name }}</h2><p class="mt-0.5 text-sm text-white/90">{{ $participation->categoryLabel() }}</p></div>
                <div class="relative z-10 w-fit rounded-xl border border-white/20 bg-white/15 px-4 py-2.5 shadow-xs backdrop-blur-md"><p class="text-xs font-medium text-white/80">Jadwal program</p><p class="mt-0.5 text-sm font-semibold text-white">{{ $participation->program->starts_at?->format('d M Y, H:i') ?? 'Belum ditentukan' }}</p></div>
            </header>

            <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-2">
                @forelse ($participation->program->simulations as $simulation)
                    @php
                        $seq = (int) ($simulation->scenario->type->sequence ?? 1);
                        $themes = [
                            1 => [
                                'card' => 'bg-gradient-to-br from-blue-50/90 via-indigo-50/30 to-white border-blue-200/90 hover:border-blue-400 hover:shadow-theme-md',
                                'top_bar' => 'bg-blue-500',
                                'badge' => 'bg-blue-100 text-blue-700 border border-blue-200/60',
                                'dot' => 'bg-blue-500',
                                'icon_bg' => 'bg-blue-100/70 text-blue-600',
                                'divider' => 'border-blue-100',
                            ],
                            2 => [
                                'card' => 'bg-gradient-to-br from-purple-50/90 via-fuchsia-50/30 to-white border-purple-200/90 hover:border-purple-400 hover:shadow-theme-md',
                                'top_bar' => 'bg-purple-500',
                                'badge' => 'bg-purple-100 text-purple-700 border border-purple-200/60',
                                'dot' => 'bg-purple-500',
                                'icon_bg' => 'bg-purple-100/70 text-purple-600',
                                'divider' => 'border-purple-100',
                            ],
                            3 => [
                                'card' => 'bg-gradient-to-br from-amber-50/90 via-orange-50/30 to-white border-amber-200/90 hover:border-amber-400 hover:shadow-theme-md',
                                'top_bar' => 'bg-amber-500',
                                'badge' => 'bg-amber-100 text-amber-700 border border-amber-200/60',
                                'dot' => 'bg-amber-500',
                                'icon_bg' => 'bg-amber-100/70 text-amber-600',
                                'divider' => 'border-amber-100',
                            ],
                            4 => [
                                'card' => 'bg-gradient-to-br from-emerald-50/90 via-teal-50/30 to-white border-emerald-200/90 hover:border-emerald-400 hover:shadow-theme-md',
                                'top_bar' => 'bg-emerald-500',
                                'badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200/60',
                                'dot' => 'bg-emerald-500',
                                'icon_bg' => 'bg-emerald-100/70 text-emerald-600',
                                'divider' => 'border-emerald-100',
                            ],
                        ];
                        $theme = $themes[$seq] ?? $themes[1];
                        $session = $participation->sessions->firstWhere('assessment_program_simulation_id', $simulation->id);
                        $status = $session?->status ?? 'not_started';
                        $statusLabel = match($status) { 'submitted' => 'Selesai', 'in_progress' => 'Sedang Dikerjakan', default => 'Belum Dimulai' };
                        $statusClass = match($status) {
                            'submitted' => 'bg-emerald-100/90 text-emerald-800 border border-emerald-200',
                            'in_progress' => 'bg-amber-100/90 text-amber-800 border border-amber-300 font-bold animate-pulse',
                            default => 'bg-white/90 text-gray-600 border border-gray-200'
                        };
                    @endphp
                    <article class="relative flex flex-col justify-between overflow-hidden rounded-2xl border p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-theme-md {{ $theme['card'] }} {{ $status === 'in_progress' ? 'ring-2 ring-amber-400/50' : '' }}">
                        <div class="absolute inset-x-0 top-0 h-1 {{ $theme['top_bar'] }}"></div>
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider {{ $theme['badge'] }}">
                                        <span class="size-1.5 rounded-full {{ $theme['dot'] }}"></span>
                                        Simulasi {{ $seq }}
                                    </span>
                                    <h3 class="mt-2 font-bold text-gray-900">{{ $simulation->scenario->type->name }}</h3>
                                    @if($simulation->scenario->simulationThreePackageLabel())
                                        <p class="mt-1 text-xs leading-5 text-gray-600">{{ $participation->requiresSimulationThreeChoice() ? 'Menunggu penetapan materi Simulasi 3 oleh admin.' : $simulation->scenario->simulationThreePackageLabel() }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold shadow-xs {{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                        </div>
                        <div class="mt-5 border-t {{ $theme['divider'] }} pt-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-xs font-medium text-gray-600">
                                    <span class="flex size-6 items-center justify-center rounded-md {{ $theme['icon_bg'] }}">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    </span>
                                    <span>{{ $simulation->scenario->duration_minutes }} menit</span>
                                </div>
                                <a href="{{ route('peserta-assessment.simulations.show', $simulation) }}" class="{{ $status === 'submitted' ? 'crud-btn-secondary' : ($status === 'in_progress' ? 'inline-flex items-center gap-1 rounded-lg bg-warning-500 px-4 py-2 text-sm font-semibold text-white hover:bg-warning-600 shadow-xs' : 'crud-btn-primary') }}">{{ $status === 'in_progress' ? 'Lanjutkan' : 'Lihat Detail' }} <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
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
