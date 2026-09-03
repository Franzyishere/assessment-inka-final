@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Master Psikotes" />
<div class="rounded-2xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-5 py-4">
        <h2 class="font-semibold text-gray-900">{{ $test->name }}</h2>
        <p class="mt-1 text-sm text-gray-500">Instrumen psikotes resmi untuk seluruh pelaksanaan rekrutmen.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>@foreach(['Instrumen','Soal','Kunci Skor','Status','Aksi'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($versions as $version)
                    <tr><td class="px-5 py-4"><p class="font-medium text-gray-900">PAPI Kostick INKA</p><p class="mt-0.5 text-xs text-gray-500">Instrumen standar</p></td><td class="px-5 py-4 text-sm text-gray-600">{{ $version->questions_count }}/90</td><td class="px-5 py-4 text-sm text-gray-600">180/180</td><td class="px-5 py-4"><span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Aktif</span></td><td class="px-5 py-4"><a href="{{ route('admin.recruitment.psychotests.show', $version) }}" class="crud-btn-soft-brand">Lihat Instrumen</a></td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Instrumen psikotes belum tersedia. Hubungi administrator sistem.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
