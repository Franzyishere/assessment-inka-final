@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Simulasi Program" />
<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
    <header class="flex flex-col gap-3 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between"><div><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Program Assessment</span><h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $program->name }}</h2><p class="mt-1 text-sm text-gray-500">Daftar simulasi aktif yang ditugaskan kepada Anda.</p></div><span class="inline-flex w-fit items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-700 shadow-theme-xs">{{ $assignments->count() }} simulasi</span></header>
    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
        @forelse ($assignments as $assignment)
            @php $simulation = $assignment->programSimulation; $submitted = $simulation->sessions->where('status', 'submitted')->count(); $total = $simulation->sessions->count(); $progress = $total > 0 ? (int) round(($submitted / $total) * 100) : 0; @endphp
            <article class="group rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-theme-sm">
                <div class="flex items-start justify-between gap-4"><div><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Simulasi {{ $simulation->scenario->type->sequence }}</span><h3 class="mt-1 font-semibold text-gray-900">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</h3></div><span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Aktif</span></div>
                <div class="mt-5 flex items-center justify-between text-xs text-gray-500"><span>{{ $submitted }} dari {{ $total }} submission</span><span class="font-semibold text-gray-700">{{ $progress }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $progress }}%"></div></div>
                <div class="mt-5 flex justify-end"><a href="{{ route('asesor.simulations.show', $simulation) }}" class="crud-btn-primary">Lihat Peserta <span aria-hidden="true">→</span></a></div>
            </article>
        @empty
            <div class="col-span-full px-5 py-14 text-center text-sm text-gray-500">Seluruh simulasi dalam program ini telah selesai.</div>
        @endforelse
    </div>
</section>
<div class="mt-5"><a href="{{ route('asesor.simulations.index') }}" class="crud-btn-secondary"><span aria-hidden="true">←</span> Kembali ke Program</a></div>
@endsection
