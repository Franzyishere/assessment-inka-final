@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Monitoring Simulasi" />

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    @forelse($simulations as $simulation)
        <article class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-start justify-between gap-4"><div><span class="text-xs font-medium uppercase text-brand-500">Simulasi {{ $simulation->scenario->type->sequence }}</span><h2 class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $simulation->program->name }}</p></div><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ ucfirst($simulation->status) }}</span></div>
            <div class="mt-5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ $simulation->progress }}%"></div></div>
            <p class="mt-2 text-xs text-gray-500">{{ $simulation->progress }}% peserta telah mengumpulkan</p>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach([['Peserta', $simulation->expected_count], ['Mulai', $simulation->started_count], ['Masuk', $simulation->submitted_count], ['Dinilai', $simulation->reviewed_count], ['Aktivitas', $simulation->event_count]] as [$label, $value])
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5"><p class="text-xs text-gray-500">{{ $label }}</p><p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $value }}</p></div>
                @endforeach
            </div>
            <div class="mt-5 flex justify-end"><a href="{{ route('asesor.simulations.show', $simulation) }}" class="text-sm font-medium text-brand-500">Lihat detail peserta →</a></div>
        </article>
    @empty
        <div class="col-span-full rounded-2xl border border-gray-200 bg-white px-5 py-16 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03]">Belum ada simulasi yang dapat dimonitor.</div>
    @endforelse
</div>
@endsection
