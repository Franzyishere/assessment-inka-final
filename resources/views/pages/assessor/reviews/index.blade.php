@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Penilaian & Rekomendasi" />
@if(session('success'))<div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>@endif
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Submission Menunggu Penilaian</h2><p class="mt-1 text-sm text-gray-500">Submission dari simulasi yang ditugaskan kepada Anda.</p></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>@foreach(['Peserta', 'Program & Simulasi', 'Dikumpulkan', 'Penilaian', 'Aksi'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($sessions as $session)
                @php($review = $session->reviews->first())
                <tr>
                    <td class="px-5 py-4"><div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $session->participant->user->name }}</div><div class="text-xs text-gray-500">{{ $session->participant->user->email }}</div></td>
                    <td class="px-5 py-4"><div class="text-sm text-gray-700 dark:text-gray-300">{{ $session->programSimulation->scenario->type->name }}</div><div class="text-xs text-gray-500">{{ $session->programSimulation->program->name }}</div></td>
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $session->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $review?->status === 'submitted' ? 'bg-success-50 text-success-700' : 'bg-warning-50 text-warning-700' }}">{{ $review?->status === 'submitted' ? 'Final' : ($review ? 'Draft' : 'Belum dinilai') }}</span></td>
                    <td class="px-5 py-4"><a href="{{ route('asesor.reviews.edit', $session) }}" class="text-sm font-medium text-brand-500">{{ $review?->status === 'submitted' ? 'Lihat' : 'Nilai' }}</a></td>
                </tr>
            @empty <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada submission yang perlu dinilai.</td></tr> @endforelse
        </tbody>
    </table></div>
    @if($sessions->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $sessions->links() }}</div>@endif
</div>
@endsection
