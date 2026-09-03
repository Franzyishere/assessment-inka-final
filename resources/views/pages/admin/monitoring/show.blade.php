@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Detail Monitoring" />
<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $program->name }}</h1><p class="mt-1 text-sm text-gray-500">{{ $program->starts_at?->format('d M Y H:i') ?? '-' }} — {{ $program->ends_at?->format('d M Y H:i') ?? '-' }}</p></div><a href="{{ route('admin.monitoring.index') }}" class="crud-btn-secondary shrink-0">← Semua program</a></div>
    <div class="mt-5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $metrics['progress'] }}%"></div></div><p class="mt-2 text-xs text-gray-500">{{ $metrics['progress'] }}% simulasi telah dikumpulkan</p>
</div>

<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach([['Target Sesi', $metrics['expected']], ['Sudah Memulai', $metrics['started']], ['Dikumpulkan', $metrics['submitted']], ['Selesai Dinilai', $metrics['reviewed']], ['Aktivitas Tercatat', $metrics['events']]] as [$label, $value])
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $value }}</p></div>
    @endforeach
</div>

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Progres Peserta per Simulasi</h2><p class="mt-1 text-sm text-gray-500">Status diperbarui berdasarkan sesi dan submission peserta.</p></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-900/50"><tr><th class="sticky left-0 z-10 min-w-56 bg-gray-50 px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:bg-gray-900">Peserta</th>@foreach($program->simulations as $simulation)<th class="min-w-52 px-5 py-3 text-left"><span class="block text-xs font-medium uppercase text-gray-500">Simulasi {{ $simulation->scenario->type->sequence }}</span><span class="mt-1 block text-xs font-normal text-gray-400">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</span></th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($program->participants as $participant)
                <tr><td class="sticky left-0 bg-white px-5 py-4 dark:bg-gray-900"><div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $participant->user->name }}</div><div class="text-xs text-gray-500">{{ $participant->user->email }}</div></td>
                    @foreach($program->simulations as $simulation)
                        @php
                            $notEligible = $simulation->scenario->type->delivery_mode === 'case_response' && $simulation->scenario->simulation_package !== $participant->simulationThreePackageKey();
                            $session = $simulation->sessions->firstWhere('assessment_participant_id', $participant->id);
                            $status = $session?->status ?? 'not_started';
                            $reviewed = $session?->reviews->contains('status', 'submitted') ?? false;
                            $styles = match($status) { 'submitted' => 'bg-success-50 text-success-700', 'in_progress' => 'bg-warning-50 text-warning-700', 'expired' => 'bg-error-50 text-error-700', default => 'bg-gray-100 text-gray-600' };
                            $labels = ['not_started' => 'Belum mulai', 'in_progress' => 'Sedang dikerjakan', 'submitted' => 'Dikumpulkan', 'expired' => 'Kedaluwarsa'];
                        @endphp
                        <td class="px-5 py-4 align-top">@if($notEligible)<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-400">Tidak sesuai kategori</span>@else<span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $styles }}">{{ $labels[$status] ?? ucfirst($status) }}</span>@if($session)<div class="mt-2 space-y-1 text-xs text-gray-500"><p>Mulai: {{ $session->started_at?->format('d M H:i') ?? '-' }}</p><p>Selesai: {{ $session->submitted_at?->format('d M H:i') ?? '-' }}</p>@if($session->events->isNotEmpty())<p class="font-medium text-error-600" title="{{ $session->events->groupBy('event_type')->map->count()->map(fn($count, $type) => (\App\Models\SimulationSessionEvent::LABELS[$type] ?? str_replace('_', ' ', $type)).': '.$count)->implode(', ') }}">{{ $session->events->count() }} aktivitas tercatat</p>@endif @if($status === 'submitted')<p class="font-medium {{ $reviewed ? 'text-success-600' : 'text-warning-600' }}">{{ $reviewed ? 'Penilaian final' : 'Menunggu penilaian' }}</p>@endif</div>@endif @endif</td>
                    @endforeach
                </tr>
            @empty <tr><td colspan="{{ max(1, $program->simulations->count() + 1) }}" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada peserta pada program ini.</td></tr> @endforelse
        </tbody>
    </table></div>
</div>
@endsection
