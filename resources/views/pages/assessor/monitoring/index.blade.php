@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Monitoring Simulasi" />

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    @forelse($simulations as $simulation)
        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="h-1 bg-brand-500"></div>
            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Simulasi {{ $simulation->scenario->type->sequence }}</span><h2 class="mt-1 font-semibold text-gray-900">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $simulation->program->name }}</p></div>
                    <span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">{{ ucfirst($simulation->status) }}</span>
                </div>
                <div class="mt-5 flex items-center justify-between text-xs text-gray-500"><span>Progres pengumpulan</span><span class="font-semibold text-gray-700">{{ $simulation->progress }}%</span></div>
                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $simulation->progress }}%"></div></div>
                <p class="mt-2 text-xs text-gray-500">{{ $simulation->progress }}% peserta telah mengumpulkan</p>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    @foreach([['Peserta', $simulation->expected_count], ['Mulai', $simulation->started_count], ['Masuk', $simulation->submitted_count], ['Dinilai', $simulation->reviewed_count], ['Aktivitas', $simulation->event_count]] as [$label, $value])
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 text-center"><p class="text-xs text-gray-500">{{ $label }}</p><p class="mt-1 text-xl font-semibold text-gray-900">{{ $value }}</p></div>
                    @endforeach
                </div>
                <div class="mt-5 flex justify-end"><a href="{{ route('asesor.simulations.show', $simulation) }}" class="crud-btn-primary">Detail Peserta <span aria-hidden="true">→</span></a></div>
            </div>
        </article>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-16 text-center"><h3 class="font-medium text-gray-800">Belum ada simulasi untuk dimonitor</h3><p class="mt-1 text-sm text-gray-500">Data akan muncul setelah Admin HCGA memberikan penugasan.</p></div>
    @endforelse
</div>
@endsection
