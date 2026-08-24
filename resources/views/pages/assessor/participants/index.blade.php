@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Peserta Assessment" />

<div class="space-y-6">
    @forelse($programs as $program)
        <section class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $program->name }}</h2></div>
                <span class="text-sm text-gray-500">{{ $program->participants->where('status', 'assigned')->count() }} peserta aktif</span>
            </div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>@foreach(['Peserta', 'Status Penugasan', 'Progres Simulasi', 'Dikumpulkan'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($program->participants as $participant)
                        <tr>
                            <td class="px-5 py-4"><div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $participant->user->name }}</div><div class="text-xs text-gray-500">{{ $participant->user->email }}</div></td>
                            <td class="px-5 py-4"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ ucfirst($participant->status) }}</span></td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $participant->started_sessions_count }} / {{ $participant->assigned_simulations_count }} dimulai</td>
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ $participant->submitted_sessions_count }} / {{ $participant->assigned_simulations_count }}</td>
                        </tr>
                    @empty <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada peserta pada program ini.</td></tr> @endforelse
                </tbody>
            </table></div>
        </section>
    @empty
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-16 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03]">Belum ada peserta dari simulasi yang ditugaskan kepada Anda.</div>
    @endforelse
</div>
@endsection
