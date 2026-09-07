@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Monitoring Program Assessment" />
<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4"><x-common.search-form :action="route('asesor.monitoring.index')" placeholder="Cari program assessment..." /></div>
<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
@forelse($programs as $program)
    <article class="rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="font-semibold text-gray-900">{{ $program->name }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ $program->participants_count }} peserta · {{ $program->simulations_count }} simulasi ditugaskan</p>
        <p class="mt-2 text-sm text-gray-500">{{ $program->starts_at?->format('d M Y H:i') ?? 'Jadwal belum ditentukan' }}</p>
        <div class="mt-5"><a class="crud-btn-primary" href="{{ route('asesor.monitoring.program', $program) }}">Lihat Monitoring</a></div>
    </article>
@empty
    <p class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">Program assessment tidak ditemukan.</p>
@endforelse
</div>
<div class="mt-5">{{ $programs->links() }}</div>
@endsection
