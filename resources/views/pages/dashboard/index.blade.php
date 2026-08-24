@extends('layouts.app')

@section('content')
    <div class="dashboard-hero">
        <div class="relative z-1 max-w-2xl">
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.22em] text-white/70">Assessment Management System</p>
            <h1 class="text-2xl font-semibold text-white sm:text-3xl">{{ $title }}</h1>
            <p class="mt-2 max-w-xl text-sm leading-6 text-white/75">{{ $description }}</p>
        </div>
    </div>

    <div class="relative z-10 -mt-16 grid grid-cols-1 gap-4 px-3 sm:grid-cols-2 md:gap-6 md:px-6 xl:grid-cols-3">
        @foreach ($metrics as $metric)
            <div class="dashboard-metric-card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</span>
                        <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $metric['value'] }}</h2>
                    </div>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 19V9m7 10V5m7 14v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 border-t border-gray-100 pt-3 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">{{ $metric['description'] }}</p>
            </div>
        @endforeach
    </div>
@endsection
