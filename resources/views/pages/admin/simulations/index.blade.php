@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Bank Simulasi" />


<div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
    Kelola materi dan durasi untuk setiap rangkaian simulasi assessment.
</div>

<div class="space-y-4">
    @foreach($simulationTypes as $type)
        @php($items = $simulationGroups->get($type->id, collect()))
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="flex items-center gap-3"><span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-sm font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $type->sequence }}</span><div><h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $type->name }}</h2><p class="mt-0.5 text-sm text-gray-500">{{ $type->description }}</p></div></div>
                @if($type->code !== \App\Models\SimulationType::CRITICAL_INCIDENT && $items->first())
                    <a href="{{ route('admin.simulations.edit', $items->first()) }}" class="crud-btn-soft-brand">Edit Materi</a>
                @endif
            </div>

            @if($type->code === \App\Models\SimulationType::CRITICAL_INCIDENT)
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach(\App\Models\SimulationScenario::SIMULATION_THREE_PACKAGES as $package => $meta)
                        @php($scenario = $items->firstWhere('simulation_package', $package))
                        <div class="flex flex-col gap-3 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div><p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $meta['label'] }}</p><p class="mt-0.5 text-xs text-gray-500">{{ $meta['audience'] }} · {{ $scenario?->materialPages->count() ?? 0 }} materi PDF · {{ $scenario?->duration_minutes ?? 60 }} menit</p></div>
                            @if($scenario)<a href="{{ route('admin.simulations.edit', $scenario) }}" class="crud-btn-soft-brand">Edit Materi</a>@endif
                        </div>
                    @endforeach
                </div>
            @else
                @php($scenario = $items->first())
                <div class="grid grid-cols-2 gap-3 px-5 py-3 text-sm sm:max-w-md">
                    <div><span class="block text-xs text-gray-500">Materi PDF</span><span class="mt-1 block font-medium text-gray-700 dark:text-gray-300">{{ $scenario?->materialPages->count() ?? 0 }}</span></div>
                    <div><span class="block text-xs text-gray-500">Durasi</span><span class="mt-1 block font-medium text-gray-700 dark:text-gray-300">{{ $scenario?->duration_minutes ? $scenario->duration_minutes.' menit' : 'Tanpa timer' }}</span></div>
                </div>
            @endif
        </section>
    @endforeach
</div>
@endsection
