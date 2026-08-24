@extends('layouts.app')

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-white p-6 md:p-8 dark:border-gray-800 dark:bg-white/[0.03]">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $description }}</p>
    </div>
@endsection
