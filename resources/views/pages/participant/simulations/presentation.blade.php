@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Upload Presentasi" />
<div class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <span class="text-xs font-medium uppercase text-brand-500">Simulasi Presentasi</span>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $programSimulation->scenario->type->name }}</h1>
    <p class="mt-3 text-sm leading-6 text-gray-500">Unggah file presentasi final yang nantinya akan ditampilkan dan dinilai oleh asesor.</p>

    <div class="mt-6 rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400">
        Format yang diterima hanya PDF. Ukuran maksimal 25 MB. Setelah dikumpulkan, file tidak dapat diganti.
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ route('peserta-assessment.simulations.presentation.submit', $programSimulation) }}" class="mt-6" onsubmit="return confirm('Pastikan file sudah benar. Kumpulkan presentasi sekarang?')">
        @csrf
        <label for="presentation" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">File Presentasi *</label>
        <input id="presentation" type="file" name="presentation" accept="application/pdf,.pdf" required class="block w-full rounded-lg border border-gray-300 bg-transparent p-3 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 dark:border-gray-700 dark:text-gray-300">
        @error('presentation')<p class="mt-2 text-xs text-error-500">{{ $message }}</p>@enderror
        <div class="mt-6 flex justify-end gap-3"><a href="{{ route('peserta-assessment.simulations.show', $programSimulation) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Kembali</a><button class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Kumpulkan Presentasi</button></div>
    </form>
</div>
@endsection
