@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Penilaian & Rekomendasi" />


@php
    $finalCount = $sessions->getCollection()->filter(fn ($session) => $session->reviews->first()?->status === 'submitted')->count();
    $pendingCount = $sessions->count() - $finalCount;
@endphp

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Perlu ditinjau</p><p class="mt-2 text-3xl font-semibold text-warning-600">{{ $pendingCount }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs"><p class="text-sm text-gray-500">Penilaian final</p><p class="mt-2 text-3xl font-semibold text-success-600">{{ $finalCount }}</p></div>
</div>

<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
    <header class="border-b border-gray-200 px-5 py-5"><h2 class="font-semibold text-gray-900">Submission Peserta</h2><p class="mt-1 text-sm text-gray-500">Berikan penilaian dan rekomendasi pada simulasi yang telah dikumpulkan.</p></header>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50"><tr>@foreach(['Peserta', 'Program & Simulasi', 'Dikumpulkan', 'Status Penilaian', 'Aksi'] as $heading)<th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($sessions as $session)
                @php
                    $review = $session->reviews->first();
                @endphp
                <tr class="transition hover:bg-gray-50/80">
                    <td class="px-5 py-4"><div class="text-sm font-medium text-gray-900">{{ $session->participant->user->name }}</div><div class="text-xs text-gray-500">{{ $session->participant->user->email }}</div></td>
                    <td class="px-5 py-4"><div class="text-sm font-medium text-gray-700">{{ $session->programSimulation->scenario->type->name }}</div><div class="mt-0.5 text-xs text-gray-500">{{ $session->programSimulation->program->name }}</div></td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">{{ $session->submitted_at?->format('d M Y, H:i') ?? '-' }}</td>
                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $review?->status === 'submitted' ? 'bg-success-50 text-success-700' : ($review ? 'bg-warning-50 text-warning-700' : 'bg-error-50 text-error-700') }}">{{ $review?->status === 'submitted' ? 'Final' : ($review ? 'Draft' : 'Belum Dinilai') }}</span></td>
                    <td class="px-5 py-4"><a href="{{ route('asesor.reviews.edit', $session) }}" class="{{ $review?->status === 'submitted' ? 'crud-btn-secondary' : 'crud-btn-primary' }}">{{ $review?->status === 'submitted' ? 'Lihat Hasil' : 'Beri Penilaian' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-16 text-center"><h3 class="font-medium text-gray-800">Belum ada submission</h3><p class="mt-1 text-sm text-gray-500">Submission peserta yang siap dinilai akan muncul di sini.</p></td></tr>
            @endforelse
        </tbody>
    </table></div>
    @if($sessions->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $sessions->links() }}</div>@endif
</section>
@endsection
