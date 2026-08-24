@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 md:gap-6">
        @foreach ($metrics as $metric)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</span>
                <h2 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $metric['value'] }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $metric['description'] }}</p>
            </div>
        @endforeach
    </div>
@endsection
