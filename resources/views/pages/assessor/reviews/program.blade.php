@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Hasil Assessment Peserta" />

<div class="mb-6 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs sm:flex-row sm:items-center sm:justify-between">
    <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Program Assessment</p><h1 class="mt-1 text-xl font-semibold text-gray-900">{{ $program->name }}</h1><p class="mt-1 text-sm text-gray-500">{{ $program->starts_at?->format('d M Y, H:i') ?? 'Jadwal belum ditentukan' }}</p></div>
    <a href="{{ route('asesor.reviews.index') }}" class="crud-btn-secondary">Kembali ke Program</a>
</div>
<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs"><x-common.search-form :action="route('asesor.reviews.program', $program)" placeholder="Cari nama atau email peserta..." /></div>

<div class="space-y-5">
    @forelse($participantSessions as $sessions)
        @php
            $participant = $sessions->first()->participant;
        @endphp
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <header class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-600">{{ strtoupper(substr($participant->user->name, 0, 1)) }}</span><div class="min-w-0"><h2 class="truncate font-semibold text-gray-900">{{ $participant->user->name }}</h2><p class="truncate text-xs text-gray-500">{{ $participant->user->email }}</p></div></div>
                <span class="text-xs font-medium text-gray-500">{{ $sessions->count() }} simulasi dikumpulkan</span>
            </header>
            <div class="divide-y divide-gray-100">
                @foreach($sessions as $session)
                    <div class="flex flex-col gap-4 px-4 py-3 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0"><p class="font-medium text-gray-800">{{ $session->programSimulation->scenario->type->name }}</p><p class="mt-1 text-xs text-gray-500">Dikumpulkan {{ $session->submitted_at?->format('d M Y, H:i') ?? '-' }}</p></div>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('asesor.reviews.edit', $session) }}" class="crud-btn-primary">Lihat &amp; Unduh Jawaban</a>
                            @if($session->submissions_exists)
                                <a href="{{ route('asesor.reviews.download', [$session, 'format' => 'pdf']) }}" class="crud-btn-secondary" aria-label="Unduh jawaban PDF {{ $session->programSimulation->scenario->type->name }}">PDF</a>
                                <a href="{{ route('asesor.reviews.download', [$session, 'format' => 'word']) }}" class="crud-btn-secondary" aria-label="Unduh jawaban Word {{ $session->programSimulation->scenario->type->name }}">Word</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <section class="rounded-2xl border border-gray-200 bg-white px-5 py-16 text-center shadow-theme-xs"><h2 class="font-medium text-gray-800">Belum ada jawaban yang dikumpulkan</h2><p class="mt-1 text-sm text-gray-500">Peserta akan muncul setelah mengumpulkan salah satu simulasi.</p></section>
    @endforelse
</div>
@endsection
