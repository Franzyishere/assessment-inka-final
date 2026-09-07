@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Peserta Penilaian" />

<div class="mb-6 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:flex-row sm:items-center sm:justify-between">
    <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Program Assessment</p><h1 class="mt-1 text-xl font-semibold text-gray-900">{{ $program->name }}</h1><p class="mt-1 text-sm text-gray-500">{{ $program->starts_at?->format('d M Y, H:i') ?? 'Jadwal belum ditentukan' }}</p></div>
    <a href="{{ route('asesor.reviews.index') }}" class="crud-btn-secondary">Kembali ke Program</a>
</div>
<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs"><x-common.search-form :action="route('asesor.reviews.program', $program)" placeholder="Cari nama atau email peserta..." /></div>

<div class="space-y-5">
    @forelse($participantSessions as $sessions)
        @php
            $participant = $sessions->first()->participant;
            $finalCount = $sessions->filter(fn ($session) => $session->reviews->first()?->status === 'submitted')->count();
        @endphp
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <header class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-600">{{ strtoupper(substr($participant->user->name, 0, 1)) }}</span><div class="min-w-0"><h2 class="truncate font-semibold text-gray-900">{{ $participant->user->name }}</h2><p class="truncate text-xs text-gray-500">{{ $participant->user->email }}</p></div></div>
                <span class="text-xs font-medium text-gray-500">{{ $finalCount }} dari {{ $sessions->count() }} penilaian final</span>
            </header>
            <div class="divide-y divide-gray-100">
                @foreach($sessions as $session)
                    @php($review = $session->reviews->first())
                    <div class="flex flex-col gap-4 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0"><p class="font-medium text-gray-800">{{ $session->programSimulation->scenario->type->name }}</p><p class="mt-1 text-xs text-gray-500">Dikumpulkan {{ $session->submitted_at?->format('d M Y, H:i') ?? '-' }}</p></div>
                        <div class="flex flex-wrap items-center gap-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $review?->status === 'submitted' ? 'bg-success-50 text-success-700' : ($review ? 'bg-warning-50 text-warning-700' : 'bg-error-50 text-error-700') }}">{{ $review?->status === 'submitted' ? 'Final' : ($review ? 'Draft' : 'Belum Dinilai') }}</span><a href="{{ route('asesor.reviews.edit', $session) }}" class="{{ $review?->status === 'submitted' ? 'crud-btn-secondary' : 'crud-btn-primary' }}">{{ $review?->status === 'submitted' ? 'Lihat Hasil' : 'Beri Penilaian' }}</a></div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <section class="rounded-2xl border border-gray-200 bg-white px-5 py-16 text-center shadow-theme-xs"><h2 class="font-medium text-gray-800">Belum ada peserta yang siap dinilai</h2><p class="mt-1 text-sm text-gray-500">Peserta akan muncul setelah mengumpulkan salah satu simulasi.</p></section>
    @endforelse
</div>
@endsection
