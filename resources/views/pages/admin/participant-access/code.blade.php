@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Kode Akses Peserta" />
<section class="mx-auto max-w-lg rounded-xl border border-gray-200 bg-white p-6 text-center dark:bg-gray-900">
    <h2 class="text-xl font-semibold">{{ $participant->user->name }}</h2>
    <p class="mt-2">{{ $participant->user->employee_number ?: 'NIP belum diisi' }}</p>
    <p class="mt-2 text-sm">{{ $program->name }}</p>
    <p class="my-6 select-all font-mono text-3xl font-bold tracking-widest">{{ $code->secret }}</p>
    <p class="mb-5 text-sm text-gray-500">Kode pribadi, bukan kode bersama. Bagikan hanya kepada peserta yang tercantum.</p>
    <a href="{{ route('admin.participant-access.show', $program) }}" class="crud-btn-secondary">Tutup</a>
</section>
@endsection
