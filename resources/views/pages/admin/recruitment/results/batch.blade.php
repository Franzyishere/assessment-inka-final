@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb :pageTitle="$batch->name" />

<div class="mb-5 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('admin.recruitment-results.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">← Kembali ke daftar batch</a>
        <h1 class="mt-3 text-xl font-semibold text-gray-900">{{ $batch->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Profil hasil peserta yang telah menyelesaikan psikotes.</p>
    </div>
    <div class="rounded-xl bg-brand-50 px-4 py-3 text-center">
        <p class="text-xs font-medium uppercase text-brand-600">Hasil Tersedia</p>
        <p class="mt-1 text-2xl font-semibold text-brand-700">{{ $results->total() }}</p>
    </div>
</div>

<div class="rounded-2xl border border-gray-200 bg-white">
    <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-semibold text-gray-900">Daftar Peserta</h2>
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ $search }}" placeholder="Cari nama atau email peserta" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm sm:w-72">
            <button class="crud-btn-secondary">Cari</button>
            @if ($search !== '')
                <a href="{{ route('admin.recruitment-results.batch', $batch) }}" class="crud-btn-soft-neutral">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['Nomor Peserta', 'Nama', 'Email', 'Total Role', 'Total Need', 'Waktu Selesai', 'Aksi'] as $heading)
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($results as $result)
                    @php
                        $participant = $result->session?->participant;
                        $user = $participant?->user;
                    @endphp
                    <tr class="transition hover:bg-gray-50/70">
                        <td class="px-5 py-4 text-sm font-medium text-gray-800">{{ $participant?->participant_number ?? '-' }}</td>
                        <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $user?->name ?? '-' }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500">{{ $user?->email ?? '-' }}</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-700">{{ $result->total_role }}</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-700">{{ $result->total_need }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500">{{ $result->session?->submitted_at?->format('d M Y, H:i') ?? '-' }}</td>
                        <td class="px-5 py-4"><a href="{{ route('admin.recruitment-results.show', $result) }}" class="crud-btn-soft-brand">Lihat Profil</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-gray-500">Peserta tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($results->hasPages())
        <div class="border-t border-gray-200 px-5 py-4">{{ $results->links() }}</div>
    @endif
</div>
@endsection
