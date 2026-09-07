@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Penilaian & Rekomendasi" />

<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
    <header class="flex flex-col gap-4 border-b border-gray-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div><h2 class="font-semibold text-gray-900">Program Assessment</h2><p class="mt-1 text-sm text-gray-500">Pilih program untuk melihat peserta dan simulasi yang perlu dinilai.</p></div>
        <x-common.search-form :action="route('asesor.reviews.index')" placeholder="Cari program..." />
    </header>

    <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($programs as $program)
            @php
                $sessions = $program->simulations->pluck('sessions')->flatten();
                $participantCount = $sessions->pluck('assessment_participant_id')->unique()->count();
                $finalCount = $sessions->filter(fn ($session) => $session->reviews->first()?->status === 'submitted')->count();
                $pendingCount = $sessions->count() - $finalCount;
            @endphp
            <article class="flex min-h-64 flex-col rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-theme-md">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5.5 3.5h9a1.5 1.5 0 0 1 1.5 1.5v11.5H4V5a1.5 1.5 0 0 1 1.5-1.5ZM7 7h6m-6 3h6m-6 3h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $pendingCount > 0 ? 'bg-warning-50 text-warning-700' : 'bg-success-50 text-success-700' }}">{{ $pendingCount > 0 ? $pendingCount.' perlu dinilai' : 'Penilaian selesai' }}</span>
                </div>
                <h3 class="mt-5 text-lg font-semibold text-gray-900">{{ $program->name }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $program->starts_at?->format('d M Y') ?? 'Jadwal belum ditentukan' }}</p>
                <div class="mt-5 grid grid-cols-3 divide-x divide-gray-200 rounded-xl bg-gray-50 py-3 text-center">
                    <div><p class="text-lg font-semibold text-gray-800">{{ $participantCount }}</p><p class="text-[11px] text-gray-500">Peserta</p></div>
                    <div><p class="text-lg font-semibold text-warning-600">{{ $pendingCount }}</p><p class="text-[11px] text-gray-500">Ditinjau</p></div>
                    <div><p class="text-lg font-semibold text-success-600">{{ $finalCount }}</p><p class="text-[11px] text-gray-500">Final</p></div>
                </div>
                <a href="{{ route('asesor.reviews.program', $program) }}" class="crud-btn-primary mt-auto w-full">Lihat Peserta</a>
            </article>
        @empty
            <div class="col-span-full py-14 text-center"><h3 class="font-medium text-gray-800">Belum ada program penilaian</h3><p class="mt-1 text-sm text-gray-500">Program yang ditugaskan kepada Anda akan muncul di sini.</p></div>
        @endforelse
    </div>

    @if($programs->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $programs->links() }}</div>@endif
</section>
@endsection
