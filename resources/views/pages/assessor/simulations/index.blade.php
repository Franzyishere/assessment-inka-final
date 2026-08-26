@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Simulasi Ditugaskan" />

@php
    $submissionCount = $assignments->sum(fn ($assignment) => $assignment->programSimulation->sessions->where('status', 'submitted')->count());
@endphp

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Penugasan aktif</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ $assignments->count() }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Submission masuk</p><p class="mt-2 text-3xl font-semibold text-brand-600">{{ $submissionCount }}</p></div>
</div>

<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
    <header class="flex flex-col gap-2 border-b border-gray-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div><h2 class="font-semibold text-gray-900">Daftar Penugasan Aktif</h2><p class="mt-1 text-sm text-gray-500">Buka simulasi untuk memantau progres dan memeriksa submission peserta.</p></div>
        <span class="inline-flex w-fit items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $assignments->count() }} simulasi</span>
    </header>
    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
        @forelse ($assignments as $assignment)
            @php
                $simulation = $assignment->programSimulation;
                $submitted = $simulation->sessions->where('status', 'submitted')->count();
                $total = $simulation->sessions->count();
                $progress = $total > 0 ? (int) round(($submitted / $total) * 100) : 0;
            @endphp
            <article class="group rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-theme-sm">
                <div class="flex items-start justify-between gap-4">
                    <div><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Simulasi {{ $simulation->scenario->type->sequence }}</span><h3 class="mt-1 font-semibold text-gray-900">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</h3><p class="mt-1 text-sm text-gray-500">{{ $simulation->program->name }}</p></div>
                    <span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Aktif</span>
                </div>
                <div class="mt-5 flex items-center justify-between text-xs text-gray-500"><span>{{ $submitted }} dari {{ $total }} submission</span><span class="font-semibold text-gray-700">{{ $progress }}%</span></div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $progress }}%"></div></div>
                <div class="mt-5 flex justify-end"><a href="{{ route('asesor.simulations.show', $simulation) }}" class="crud-btn-primary">Lihat Peserta <span aria-hidden="true">→</span></a></div>
            </article>
        @empty
            <div class="col-span-full px-5 py-16 text-center"><div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 7V3m8 4V3M4 11h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"/></svg></div><h3 class="mt-4 font-medium text-gray-800">Tidak ada penugasan aktif</h3><p class="mt-1 text-sm text-gray-500">Simulasi selesai tetap tersedia pada menu Penilaian & Rekomendasi.</p></div>
        @endforelse
    </div>
</section>
@endsection
