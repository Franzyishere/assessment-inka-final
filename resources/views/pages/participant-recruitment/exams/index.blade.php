@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Ujian Saya" />
<div class="rounded-2xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-5 py-4"><h1 class="font-semibold text-gray-900">Daftar Psikotes</h1><p class="mt-1 text-sm text-gray-500">Ujian ditampilkan sesuai batch rekrutmen dan jadwal yang ditetapkan.</p></div>
    <div class="divide-y divide-gray-100">
        @forelse($assignments as $assignment)
            @php($session = $assignment->sessions->sortByDesc('attempt_number')->first())
            @php($notStarted = $assignment->available_from?->isFuture())
            @php($ended = $assignment->available_until?->isPast())
            <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-semibold text-gray-900">{{ $assignment->version->test->name }}</p><p class="mt-1 text-sm text-gray-500">{{ $assignment->batch->name }} · {{ $assignment->duration_minutes ? $assignment->duration_minutes.' menit' : 'Durasi belum ditentukan' }}</p><p class="mt-1 text-xs text-gray-400">Jadwal: {{ $assignment->available_from?->format('d M Y H:i') ?? 'mengikuti arahan admin' }} – {{ $assignment->available_until?->format('d M Y H:i') ?? 'tanpa batas akhir' }}</p></div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $session ? ucfirst(str_replace('_', ' ', $session->status)) : ($notStarted ? 'Belum dibuka' : ($ended ? 'Berakhir' : 'Belum dimulai')) }}</span>
                    @if($session?->status === 'in_progress')<a href="{{ route('peserta-rekrutmen.exams.take', $session) }}" class="crud-btn-primary">Lanjutkan</a>
                    @elseif(!$session && !$ended)<a href="{{ route('peserta-rekrutmen.exams.instructions', $assignment) }}" class="crud-btn-primary">Lihat Instruksi</a>@endif
                </div>
            </div>
        @empty
            <div class="px-5 py-14 text-center"><p class="font-medium text-gray-700">Belum ada psikotes aktif</p><p class="mt-1 text-sm text-gray-500">Ujian akan muncul setelah admin menugaskan psikotes ke batch Anda.</p></div>
        @endforelse
    </div>
</div>
@endsection
