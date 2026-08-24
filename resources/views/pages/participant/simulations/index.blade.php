@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Simulasi Saya" />
@if (session('success'))<div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>@endif

<div class="space-y-6">
    @forelse ($participations as $participation)
        <section class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $participation->program->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $participation->program->starts_at?->format('d M Y H:i') ?? 'Jadwal belum ditentukan' }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
                @forelse ($participation->program->simulations as $simulation)
                    @php($session = $participation->sessions->firstWhere('assessment_program_simulation_id', $simulation->id))
                    <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-4">
                            <div><span class="text-xs font-medium uppercase text-brand-500">Simulasi {{ $simulation->scenario->type->sequence }}</span><h3 class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $simulation->scenario->type->name }}</h3>@if($simulation->scenario->simulationThreePackageLabel())<p class="mt-1 text-xs text-gray-500">{{ $participation->requiresSimulationThreeChoice() ? 'Pilih jalur CI 3 atau In-Tray 3 sebelum mulai' : $simulation->scenario->simulationThreePackageLabel() }}</p>@endif</div>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ str_replace('_', ' ', $session?->status ?? 'belum dimulai') }}</span>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-sm text-gray-500"><span>{{ $simulation->scenario->duration_minutes }} menit</span><a href="{{ route('peserta-assessment.simulations.show', $simulation) }}" class="font-medium text-brand-500 hover:text-brand-600">Lihat detail →</a></div>
                    </article>
                @empty <div class="col-span-full rounded-xl bg-success-50 px-4 py-8 text-center text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">Tidak ada simulasi aktif. Simulasi yang selesai atau telah melewati waktu pelaksanaan disembunyikan dari daftar ini.</div> @endforelse
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-gray-200 bg-white px-6 py-16 text-center dark:border-gray-800 dark:bg-white/[0.03]"><h2 class="font-semibold text-gray-800 dark:text-white/90">Belum ada program assessment</h2><p class="mt-2 text-sm text-gray-500">Program akan muncul setelah Anda ditugaskan oleh Admin HCGA.</p></div>
    @endforelse
</div>
@endsection
