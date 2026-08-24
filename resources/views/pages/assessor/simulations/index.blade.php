@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Simulasi Ditugaskan" />
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Daftar Penugasan Aktif</h2><p class="mt-1 text-sm text-gray-500">Simulasi disembunyikan otomatis setelah seluruh peserta yang relevan selesai mengumpulkan. Data tetap tersedia pada Penilaian & Rekomendasi.</p></div>
    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
        @forelse ($assignments as $assignment)
            @php($simulation = $assignment->programSimulation)
            <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <span class="text-xs font-medium uppercase text-brand-500">{{ $simulation->scenario->type->name }}</span>
                <h3 class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $simulation->program->name }}</p>
                <div class="mt-4 flex items-center justify-between"><span class="text-xs text-gray-500">{{ $simulation->sessions->where('status', 'submitted')->count() }} submission masuk</span><a href="{{ route('asesor.simulations.show', $simulation) }}" class="text-sm font-medium text-brand-500">Lihat peserta →</a></div>
            </article>
        @empty <div class="col-span-full py-12 text-center text-sm text-gray-500">Tidak ada penugasan aktif. Simulasi yang selesai dapat dilihat pada Penilaian & Rekomendasi.</div> @endforelse
    </div>
</div>
@endsection
