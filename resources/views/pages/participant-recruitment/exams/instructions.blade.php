@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Instruksi PAPI Kostick" />
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8">
        <h1 class="text-xl font-semibold text-gray-900">Petunjuk Pengerjaan</h1>
        <div class="mt-5 space-y-4 text-sm leading-7 text-gray-600">
            @include('pages.participant-recruitment.exams._static-instructions')
        </div>
        <div class="mt-6 grid gap-3 rounded-xl bg-gray-50 p-4 text-sm sm:grid-cols-3"><div><p class="text-gray-500">Jumlah soal</p><p class="mt-1 font-semibold">90 pasangan</p></div><div><p class="text-gray-500">Durasi</p><p class="mt-1 font-semibold">{{ $assignment->duration_minutes ? $assignment->duration_minutes.' menit' : 'Belum ditentukan' }}</p></div><div><p class="text-gray-500">Kesempatan</p><p class="mt-1 font-semibold">1 kali</p></div></div>
        <form method="POST" action="{{ route('peserta-rekrutmen.exams.start', $assignment) }}" class="mt-7 flex justify-end">@csrf<button class="crud-btn-primary" @disabled($session?->status === 'submitted' || !$assignment->duration_minutes)>{{ $session?->status === 'in_progress' ? 'Lanjutkan Ujian' : 'Saya Paham, Mulai Ujian' }}</button></form>
    </div>
</div>
@endsection
