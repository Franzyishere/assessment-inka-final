@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Simulasi Ditugaskan" />
@php $assignmentCount = $programs->sum('assignments_count'); $submissionCount = $programs->sum('submission_count'); @endphp
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Program aktif</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ $programs->count() }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Penugasan aktif</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ $assignmentCount }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Submission masuk</p><p class="mt-2 text-3xl font-semibold text-brand-600">{{ $submissionCount }}</p></div>
</div>
<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
    <header class="flex flex-col gap-4 border-b border-gray-200 px-5 py-5 lg:flex-row lg:items-center lg:justify-between"><div><h2 class="font-semibold text-gray-900">Daftar Program Assessment</h2><p class="mt-1 text-sm text-gray-500">Pilih program untuk melihat simulasi yang masih aktif.</p></div><div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center lg:w-auto"><x-common.search-form :action="route('asesor.simulations.index')" placeholder="Cari program..." /><span class="inline-flex w-fit shrink-0 items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $programs->count() }} program</span></div></header>
    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
        @forelse ($programs as $item)
            @php($program = $item['program'])
            <article class="group rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-theme-sm">
                <div class="flex items-start justify-between gap-4"><div><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Program Assessment</span><h3 class="mt-1 font-semibold text-gray-900">{{ $program->name }}</h3><p class="mt-1 text-sm text-gray-500">{{ $program->starts_at?->format('d M Y, H:i') ?? 'Jadwal belum ditentukan' }}</p></div><span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Aktif</span></div>
                <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-xl bg-gray-50 px-4 py-3"><p class="text-xs text-gray-500">Simulasi</p><p class="mt-1 text-lg font-semibold text-gray-900">{{ $item['assignments_count'] }}</p></div><div class="rounded-xl bg-gray-50 px-4 py-3"><p class="text-xs text-gray-500">Submission</p><p class="mt-1 text-lg font-semibold text-gray-900">{{ $item['submission_count'] }}</p></div></div>
                <div class="mt-5 flex justify-end"><a href="{{ route('asesor.simulations.program', $program) }}" class="crud-btn-primary">Lihat Simulasi <span aria-hidden="true">→</span></a></div>
            </article>
        @empty
            <div class="col-span-full px-5 py-16 text-center"><h3 class="font-medium text-gray-800">Tidak ada penugasan aktif</h3><p class="mt-1 text-sm text-gray-500">Simulasi selesai tetap tersedia pada menu Penilaian & Rekomendasi.</p></div>
        @endforelse
    </div>
</section>
@endsection
