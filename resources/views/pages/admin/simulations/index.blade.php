@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Bank Simulasi" />

<div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
    Kelola materi dan durasi untuk setiap rangkaian simulasi assessment.
</div>

<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs">
    <x-common.search-form :action="route('admin.simulations.index')" placeholder="Cari simulasi, paket, atau nama materi PDF..." />
</div>

<div class="space-y-4">
    @foreach($simulationTypes as $type)
        @php
            $items = $simulationGroups->get($type->id, collect());
        @endphp
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-sm font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        {{ $type->sequence }}
                    </span>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $type->name }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500">{{ $type->description }}</p>
                    </div>
                </div>
                @if($type->code !== \App\Models\SimulationType::CRITICAL_INCIDENT && $items->first())
                    <a href="{{ route('admin.simulations.edit', $items->first()) }}" class="crud-btn-soft-brand shrink-0">
                        {{ $type->delivery_mode === 'file_upload' ? 'Pengaturan Simulasi' : 'Edit Materi' }}
                    </a>
                @endif
            </div>

            @if($type->code === \App\Models\SimulationType::CRITICAL_INCIDENT)
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach(\App\Models\SimulationScenario::SIMULATION_THREE_PACKAGES as $package => $meta)
                        @php
                            $scenario = $items->firstWhere('simulation_package', $package);
                            $materials = $scenario?->materialPages ?? collect();
                        @endphp
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $meta['label'] }}</p>
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        {{ $meta['audience'] }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 pt-0.5">
                                    @if($materials->isNotEmpty())
                                        @foreach($materials as $material)
                                            <div class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50/70 px-2.5 py-1 text-xs font-medium text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300 shadow-xs" title="{{ $material->attachment_name }}">
                                                <svg class="size-3.5 shrink-0 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                                                </svg>
                                                <span class="truncate max-w-[220px] sm:max-w-xs font-semibold">{{ $material->attachment_name }}</span>
                                                @if($material->attachment_size)
                                                    <span class="text-[10px] text-red-500/80">({{ number_format($material->attachment_size / 1024, 0) }} KB)</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            Belum ada materi PDF
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                        <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $scenario?->duration_minutes ?? 60 }} Menit
                                    </span>
                                </div>
                            </div>
                            @if($scenario)
                                <div class="sm:shrink-0">
                                    <a href="{{ route('admin.simulations.edit', $scenario) }}" class="crud-btn-soft-brand text-xs py-1.5 px-3">Edit Materi</a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @elseif($type->delivery_mode === 'file_upload')
                @php
                    $scenario = $items->first();
                @endphp
                <div class="px-5 py-4 bg-slate-50/50 dark:bg-white/[0.01]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1.5 max-w-2xl">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
                                    <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Unggah Mandiri Peserta (File Upload)
                                </span>
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    Mendukung format PDF / Dokumen Presentasi
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                Simulasi ini tidak memerlukan materi bank soal dari Admin. Peserta mengunggah file presentasi secara mandiri pada saat jadwal sesi berlangsung untuk dipaparkan dan dinilai langsung oleh Asesor.
                            </p>
                        </div>
                        <div class="flex items-center gap-6 sm:shrink-0 text-sm">
                            <div>
                                <span class="block text-xs text-gray-500">Durasi Sesi</span>
                                <span class="mt-0.5 inline-flex items-center gap-1 font-medium text-gray-700 dark:text-gray-300">
                                    <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $scenario?->duration_minutes ? $scenario->duration_minutes.' Menit' : 'Sesuai Jadwal Asesor' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                @php
                    $scenario = $items->first();
                    $materials = $scenario?->materialPages ?? collect();
                @endphp
                <div class="px-5 py-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Materi PDF Aktif</span>
                            @if($materials->isNotEmpty())
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach($materials as $material)
                                        <div class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50/70 px-3 py-1.5 text-xs font-medium text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300 shadow-xs" title="{{ $material->attachment_name }}">
                                            <svg class="size-4 shrink-0 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                                            </svg>
                                            <span class="truncate max-w-[280px] sm:max-w-md font-semibold">{{ $material->attachment_name }}</span>
                                            @if($material->attachment_size)
                                                <span class="text-[10px] text-red-500/80">({{ number_format($material->attachment_size / 1024, 0) }} KB)</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    Belum ada materi PDF diunggah
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-6 sm:shrink-0 text-sm">
                            <div>
                                <span class="block text-xs text-gray-500">Durasi Pengerjaan</span>
                                <span class="mt-0.5 inline-flex items-center gap-1 font-medium text-gray-700 dark:text-gray-300">
                                    <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $scenario?->duration_minutes ? $scenario->duration_minutes.' Menit' : 'Tanpa timer' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @endforeach
    @if($simulationTypes->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-14 text-center text-sm text-gray-500">Simulasi tidak ditemukan.</div>
    @endif
</div>
@endsection
