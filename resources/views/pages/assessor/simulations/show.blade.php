@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Peserta Simulasi" />

@php
    $isObservation = $programSimulation->scenario->type->delivery_mode === 'assessor_observation';
    $displayRows = $isObservation ? $programSimulation->program->participants : $programSimulation->sessions;
    $submittedCount = $isObservation
        ? $programSimulation->program->participants->filter(fn ($participant) => $programSimulation->sessions->firstWhere('assessment_participant_id', $participant->id)?->status === 'submitted')->count()
        : $programSimulation->sessions->where('status', 'submitted')->count();
@endphp

<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
    <header class="border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white px-5 py-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Simulasi {{ $programSimulation->scenario->type->sequence }}</span><h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $programSimulation->scenario->simulationThreePackageLabel() ?? $programSimulation->scenario->type->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $programSimulation->program->name }}</p></div>
            <div class="flex gap-3"><div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-center shadow-theme-xs"><p class="text-xs text-gray-500">Peserta</p><p class="mt-1 text-lg font-semibold text-gray-900">{{ $displayRows->count() }}</p></div><div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-center"><p class="text-xs text-success-700">Selesai</p><p class="mt-1 text-lg font-semibold text-success-700">{{ $submittedCount }}</p></div></div>
        </div>
    </header>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>@foreach(['Peserta','Status','Dikumpulkan','File/Jawaban'] as $heading)<th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-gray-100">
                @if($isObservation)
                    @forelse($programSimulation->program->participants as $participant)
                        @php
                            $session = $programSimulation->sessions->firstWhere('assessment_participant_id', $participant->id);
                            $status = $session?->status ?? 'not_started';
                        @endphp
                        <tr class="transition hover:bg-gray-50/80">
                            <td class="px-5 py-4"><div class="text-sm font-medium text-gray-900">{{ $participant->user->name }}</div><div class="text-xs text-gray-500">{{ $participant->user->email }}</div></td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status === 'submitted' ? 'bg-success-50 text-success-700' : ($status === 'in_progress' ? 'bg-warning-50 text-warning-700' : 'bg-gray-100 text-gray-600') }}">{{ $status === 'submitted' ? 'Selesai' : ($status === 'in_progress' ? 'Sedang Berlangsung' : 'Belum Dimulai') }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">{{ $session?->submitted_at?->format('d M Y, H:i') ?? '-' }}</td>
                            <td class="px-5 py-4">@if($session?->status === 'submitted')<a href="{{ route('asesor.reviews.edit', $session) }}" class="crud-btn-primary">Beri penilaian</a>@else<span class="text-xs text-gray-400">Menunggu peserta</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-gray-500">Belum ada peserta pada program ini.</td></tr>
                    @endforelse
                @else
                    @forelse($programSimulation->sessions as $session)
                        @php
                            $submission = $session->submissions->sortByDesc('revision')->first();
                        @endphp
                        <tr class="transition hover:bg-gray-50/80">
                            <td class="px-5 py-4"><div class="text-sm font-medium text-gray-900">{{ $session->participant->user->name }}</div><div class="text-xs text-gray-500">{{ $session->participant->user->email }}</div></td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $session->status === 'submitted' ? 'bg-success-50 text-success-700' : ($session->status === 'in_progress' ? 'bg-warning-50 text-warning-700' : 'bg-gray-100 text-gray-600') }}">{{ match($session->status) { 'submitted' => 'Selesai', 'in_progress' => 'Sedang Berlangsung', default => ucwords(str_replace('_', ' ', $session->status)) } }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">{{ $session->submitted_at?->format('d M Y, H:i') ?? '-' }}</td>
                            <td class="px-5 py-4"><div class="flex flex-wrap items-center gap-2">
                                @if($submission?->storage_path)
                                    <span class="mr-2 max-w-48 truncate text-xs text-gray-500" title="{{ $submission->original_filename }}">{{ $submission->original_filename }}</span><a target="_blank" href="{{ route('asesor.submissions.preview', $submission) }}" class="crud-btn-secondary">Preview</a><a href="{{ route('asesor.submissions.download', $submission) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Unduh</a>
                                @elseif($submission)<span class="text-xs text-gray-500">Jawaban tersimpan</span>@else<span class="text-xs text-gray-400">Belum ada jawaban</span>@endif
                                @if($session->status === 'submitted')<a href="{{ route('asesor.reviews.edit', $session) }}" class="crud-btn-primary">Nilai</a>@endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-gray-500">Belum ada peserta yang memulai simulasi.</td></tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</section>

<div class="mt-5"><a href="{{ route('asesor.simulations.index') }}" class="crud-btn-secondary"><span aria-hidden="true">←</span> Kembali</a></div>
@endsection
