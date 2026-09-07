@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Monitoring Assessment" />
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"><div><h2 class="font-semibold text-gray-800 dark:text-white/90">Pilih Program Assessment</h2><p class="mt-1 text-sm text-gray-500">Pantau progres pengerjaan dan penilaian setiap program.</p></div><x-common.search-form :action="route('admin.monitoring.index')" placeholder="Cari program..." /></div>
    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2 xl:grid-cols-3">
        @forelse($programs as $program)
            @php
                $statusClass = match ($program->status) {
                    'active' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                    'completed' => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                    'cancelled' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
                    default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
                };
            @endphp
            <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="flex items-start justify-between gap-3"><div><h3 class="font-medium text-gray-800 dark:text-white/90">{{ $program->name }}</h3></div><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">{{ ucfirst($program->status) }}</span></div>
                <div class="mt-5 grid grid-cols-2 gap-3 text-sm"><div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5"><span class="text-xs text-gray-500">Peserta</span><p class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $program->participants_count }}</p></div><div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5"><span class="text-xs text-gray-500">Simulasi</span><p class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $program->simulations_count }}</p></div></div>
                <a href="{{ route('admin.monitoring.show', $program) }}" class="crud-btn-soft-brand mt-4">Buka monitoring →</a>
            </article>
        @empty <div class="col-span-full py-12 text-center text-sm text-gray-500">Belum ada program assessment.</div> @endforelse
    </div>
    @if($programs->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $programs->links() }}</div>@endif
</div>
@endsection
