@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Monitoring Assessment" />
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Pilih Program Assessment</h2><p class="mt-1 text-sm text-gray-500">Pantau progres pengerjaan dan penilaian setiap program.</p></div>
    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2 xl:grid-cols-3">
        @forelse($programs as $program)
            <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="flex items-start justify-between gap-3"><div><h3 class="font-medium text-gray-800 dark:text-white/90">{{ $program->name }}</h3></div><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs text-brand-600">{{ ucfirst($program->status) }}</span></div>
                <div class="mt-5 grid grid-cols-2 gap-3 text-sm"><div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5"><span class="text-xs text-gray-500">Peserta</span><p class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $program->participants_count }}</p></div><div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5"><span class="text-xs text-gray-500">Simulasi</span><p class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $program->simulations_count }}</p></div></div>
                <a href="{{ route('admin.monitoring.show', $program) }}" class="crud-btn-soft-brand mt-4">Buka monitoring →</a>
            </article>
        @empty <div class="col-span-full py-12 text-center text-sm text-gray-500">Belum ada program assessment.</div> @endforelse
    </div>
    @if($programs->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $programs->links() }}</div>@endif
</div>
@endsection
