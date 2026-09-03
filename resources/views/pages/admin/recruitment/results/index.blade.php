@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Hasil Psikotes" />

<div class="rounded-2xl border border-gray-200 bg-white">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-semibold text-gray-900">Hasil Psikotes per Batch</h1>
            <p class="mt-1 text-sm text-gray-500">Pilih batch untuk melihat profil hasil peserta.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ $search }}" placeholder="Cari nama batch" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm sm:w-64">
            <button class="crud-btn-secondary">Cari</button>
            @if ($search !== '')
                <a href="{{ route('admin.recruitment-results.index') }}" class="crud-btn-soft-neutral">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['Batch Rekrutmen', 'Jadwal', 'Peserta Terdaftar', 'Hasil Tersedia', 'Status', 'Aksi'] as $heading)
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($batches as $batch)
                    <tr class="transition hover:bg-gray-50/70">
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-900">{{ $batch->name }}</p>
                            <p class="mt-1 max-w-sm truncate text-xs text-gray-500">{{ $batch->description ?: 'Pelaksanaan psikotes rekrutmen' }}</p>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-600">{{ $batch->starts_at?->format('d M Y, H:i') ?? '-' }}</td>
                        <td class="px-5 py-4 text-sm font-medium text-gray-700">{{ $batch->participants_count }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">{{ $batch->scored_results_count }} peserta</span></td>
                        <td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ ucfirst($batch->status) }}</span></td>
                        <td class="px-5 py-4"><a href="{{ route('admin.recruitment-results.batch', $batch) }}" class="crud-btn-soft-brand">Lihat Peserta</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-gray-500">Tidak ada batch dengan hasil psikotes yang tersedia.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($batches->hasPages())
        <div class="border-t border-gray-200 px-5 py-4">{{ $batches->links() }}</div>
    @endif
</div>
@endsection
