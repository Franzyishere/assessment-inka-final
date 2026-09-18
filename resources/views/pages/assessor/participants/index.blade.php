@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Peserta Assessment" />

<div class="space-y-6" x-data="{ search: '' }">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
        <label for="participant-search" class="mb-2 block text-sm font-medium text-gray-700">Cari peserta</label>
        <div class="relative max-w-xl">
            <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="participant-search" type="search" x-model.debounce.200ms="search" placeholder="Cari berdasarkan nama atau email..." class="h-11 w-full rounded-xl border border-gray-300 bg-white py-2 pl-11 pr-4 text-sm text-gray-800 outline-none transition focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10">
        </div>
    </div>

    @forelse($programs as $program)
        @php
            $activeParticipants = $program->participants->where('status', 'assigned');
        @endphp
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <header class="flex flex-col gap-3 border-b border-gray-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Program Assessment</p><h2 class="mt-1 font-semibold text-gray-900">{{ $program->name }}</h2></div>
                <span class="w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $activeParticipants->count() }} peserta aktif</span>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>@foreach(['Peserta', 'Status', 'Paket Simulasi 3', 'Progres Pengerjaan', 'Pengumpulan'] as $heading)<th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($program->participants as $participant)
                            @php
                                $total = max(1, $participant->assigned_simulations_count);
                                $percentage = (int) round(($participant->submitted_sessions_count / $total) * 100);
                                $searchValue = strtolower($participant->user->name.' '.$participant->user->email);
                            @endphp
                            <tr class="transition hover:bg-gray-50/80" x-show="!search || @js($searchValue).includes(search.toLowerCase())">
                                <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ strtoupper(substr($participant->user->name, 0, 1)) }}</span><div><div class="text-sm font-medium text-gray-900">{{ $participant->user->name }}</div><div class="text-xs text-gray-500">{{ $participant->user->email }}</div></div></div></td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $participant->status === 'assigned' ? 'bg-success-50 text-success-700' : 'bg-gray-100 text-gray-600' }}">{{ $participant->status === 'assigned' ? 'Aktif' : ucfirst($participant->status) }}</span></td>
                                <td class="min-w-64 px-5 py-4">
                                    <p class="mb-2 text-xs text-gray-500">{{ $participant->categoryLabel() }}</p>
                                    @php($selectedPackage = $participant->simulationThreePackageKey())
                                    @if($selectedPackage)
                                        <span class="text-sm font-medium text-gray-800">{{ (\App\Models\SimulationScenario::SIMULATION_THREE_PACKAGES + \App\Models\SimulationScenario::LEGACY_SIMULATION_THREE_PACKAGES)[$selectedPackage]['label'] ?? $participant->simulationThreeTrack() }}</span>
                                        <p class="mt-1 text-xs text-gray-500">Ditetapkan oleh admin.</p>
                                    @else
                                        <span class="text-sm font-medium text-warning-700">Belum ditetapkan admin</span>
                                    @endif
                                </td>
                                <td class="min-w-52 px-5 py-4"><div class="flex items-center justify-between text-xs text-gray-500"><span>{{ $participant->started_sessions_count }} dari {{ $participant->assigned_simulations_count }} dimulai</span><span>{{ $percentage }}%</span></div><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ $percentage }}%"></div></div></td>
                                <td class="px-5 py-4"><span class="text-sm font-semibold text-gray-900">{{ $participant->submitted_sessions_count }} / {{ $participant->assigned_simulations_count }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada peserta pada program ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-16 text-center"><h3 class="font-medium text-gray-800">Belum ada peserta yang dipantau</h3><p class="mt-1 text-sm text-gray-500">Peserta akan tampil setelah Anda mendapatkan penugasan program.</p></div>
    @endforelse
</div>
@endsection
