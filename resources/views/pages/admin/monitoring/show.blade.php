@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Detail Monitoring" />

<div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Program Assessment</p><h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $program->name }}</h1><p class="mt-2 text-sm font-medium text-gray-600">{{ $program->starts_at?->format('d M Y, H:i') ?? '-' }} <span class="mx-1 text-gray-400">—</span> {{ $program->ends_at?->format('d M Y, H:i') ?? '-' }}</p></div>
        <a href="{{ route('admin.monitoring.index') }}" class="crud-btn-secondary shrink-0">← Semua program</a>
    </div>
    <div class="mt-3 flex items-center gap-4"><div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $metrics['progress'] }}%"></div></div><span class="min-w-12 text-right text-sm font-bold text-brand-700">{{ $metrics['progress'] }}%</span></div>
    <p class="mt-2 text-sm text-gray-500">Progres pengumpulan seluruh simulasi peserta.</p>
</div>

<div class="mb-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
    @foreach([['Target Sesi', $metrics['expected']], ['Sudah Memulai', $metrics['started']], ['Dikumpulkan', $metrics['submitted']], ['Aktivitas Tercatat', $metrics['events']]] as [$label, $value])
        <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-xs"><p class="text-sm font-medium text-gray-600">{{ $label }}</p><p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p></div>
    @endforeach
</div>

<section class="mb-4 overflow-hidden rounded-2xl border border-gray-200 bg-white">
    <header class="px-4 py-3 border-b border-gray-200">
        <h2 class="font-semibold text-gray-900">Kontrol Simulasi</h2>
        <p class="mt-1 text-xs text-gray-600">Simulasi 1–3 dibuka admin. Presentasi tersedia secara default sampai ditutup.</p>
    </header>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([1 => 'Problem Analysis', 2 => 'Leaderless Group Discussion', 3 => 'Critical Incident / In-Tray', 4 => 'Presentasi & Wawancara'] as $seq => $name)
            @php
                $matching = $program->simulations->filter(fn ($s) => (int) ($s->scenario->type->sequence ?? 0) === $seq);
                $first = $matching->first();
                $opened = $first && ($first->status === 'in_progress' || ($first->isAlwaysOpen() && $first->status !== 'completed'));
                $closed = $first?->status === 'completed';
            @endphp
            <div class="min-w-0 border-b border-gray-100 p-4 flex flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-gray-600">Simulasi {{ $seq }}</span>
                    <span class="text-xs font-semibold {{ $opened ? 'text-success-700' : 'text-gray-600' }}">{{ !$first ? 'Belum diatur' : ($opened ? 'Dibuka' : ($closed ? 'Ditutup' : 'Belum dimulai')) }}</span>
                </div>
                <h3 class="text-sm font-semibold text-gray-900">{{ $name }}</h3>
                @if($first)
                    @php
                        $canToggle = $program->status === 'active' && ! $program->archived_at && ! $program->ends_at?->isPast();
                    @endphp
                    @if($canToggle)
                        <form class="mt-auto" method="POST" action="{{ route($opened ? 'admin.monitoring.simulations.close' : 'admin.monitoring.simulations.start', [$program, $seq]) }}"
                            x-data @submit.prevent="$dispatch('confirm-dialog', { title: '{{ $opened ? 'Tutup sesi?' : 'Buka sesi?' }}', message: '{{ $opened ? 'Akses pengerjaan dan penyimpanan baru dihentikan. Jawaban tersimpan tetap dipertahankan.' : 'Peserta dapat mengakses simulasi. Timer sesi yang sudah dimulai tidak direset.' }}', confirmLabel: '{{ $opened ? 'Tutup Sesi' : 'Buka Sesi' }}', tone: '{{ $opened ? 'danger' : 'brand' }}', onConfirm: () => $el.submit() })">
                            @csrf
                            <button type="submit" class="{{ $opened ? 'crud-btn-danger' : 'crud-btn-success' }} !h-9 w-full justify-center">{{ $opened ? 'Tutup Sesi' : ($closed ? 'Buka Kembali' : 'Mulai Simulasi') }}</button>
                        </form>
                    @else
                        <button disabled type="button" class="crud-btn-secondary !h-9 w-full justify-center opacity-60 cursor-not-allowed mt-auto" title="Program berstatus {{ $program->status }} (tidak dapat diubah)">
                            {{ $opened ? 'Sesi Terbuka' : ($closed ? 'Sesi Ditutup' : 'Belum Dimulai') }}
                        </button>
                    @endif
                @else
                    <button disabled class="crud-btn-secondary !h-9 w-full justify-center opacity-50 mt-auto">Belum tersedia</button>
                @endif
            </div>
        @endforeach
    </div>
</section>

<section x-data="{ search: '' }" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
    <header class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-bold text-gray-900">Progres Peserta per Simulasi</h2><p class="mt-1 text-sm text-gray-500">Geser tabel secara horizontal untuk melihat seluruh simulasi.</p></div><input type="search" x-model.debounce.200ms="search" placeholder="Cari nama atau email peserta..." class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm sm:max-w-xs"></header>
    <div class="overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-0">
            <thead><tr>
                <th class="sticky left-0 z-20 min-w-64 border-b border-r border-gray-200 bg-gray-100 px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700">Peserta</th>
                @foreach($program->simulations as $simulation)
                    <th class="min-w-60 border-b border-gray-200 bg-gray-100 px-5 py-4 text-left">
                        <div class="flex items-center justify-between gap-2">
                            <span class="block text-xs font-bold uppercase tracking-wider text-brand-700">Simulasi {{ $simulation->scenario->type->sequence }}</span>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $simulation->statusBadgeClass() }}">{{ $simulation->statusLabel() }}</span>
                        </div>
                        <span class="mt-1.5 block text-sm font-semibold leading-5 text-gray-800">{{ $simulation->scenario->simulationThreePackageLabel() ?? $simulation->scenario->type->name }}</span>
                    </th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse($program->participants as $participant)
                    <tr x-show="!search || @js(mb_strtolower($participant->user->name.' '.$participant->user->email)).includes(search.toLowerCase())" class="group even:bg-gray-50/60 hover:bg-brand-50/40">
                        <td class="sticky left-0 z-10 border-b border-r border-gray-200 bg-white px-6 py-5 group-even:bg-gray-50 group-hover:bg-brand-50"><div class="text-sm font-bold text-gray-900">{{ $participant->user->name }}</div><div class="mt-1 text-sm text-gray-500">{{ $participant->user->email }}</div></td>
                        @foreach($program->simulations as $simulation)
                            @php
                                $notEligible = $simulation->scenario->type->delivery_mode === 'case_response' && $simulation->scenario->simulation_package !== $participant->simulationThreePackageKey();
                                $session = $simulation->sessions->firstWhere('assessment_participant_id', $participant->id);
                                $status = $session?->status ?? 'not_started';
                                $styles = match($status) { 'submitted' => 'bg-success-50 text-success-700', 'in_progress' => 'bg-warning-50 text-warning-700', 'expired' => 'bg-error-50 text-error-700', default => 'bg-gray-100 text-gray-600' };
                                $labels = ['not_started' => 'Belum mulai', 'in_progress' => 'Sedang dikerjakan', 'submitted' => 'Dikumpulkan', 'expired' => 'Kedaluwarsa'];
                            @endphp
                            <td class="border-b border-gray-200 px-5 py-5 align-top">
                                @if($notEligible)
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-500">Tidak sesuai kategori</span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-bold {{ $styles }}">{{ $labels[$status] ?? ucfirst($status) }}</span>
                                    @if($session)
                                        <div class="mt-3 space-y-1.5 text-sm leading-5 text-gray-600">
                                            <p><span class="font-medium text-gray-700">Mulai:</span> {{ $session->started_at?->format('d M, H:i') ?? '-' }}</p>
                                            <p><span class="font-medium text-gray-700">Selesai:</span> {{ $session->submitted_at?->format('d M, H:i') ?? '-' }}</p>
                                            @if($session->events->isNotEmpty())<p class="font-semibold text-error-600" title="{{ $session->events->groupBy('event_type')->map->count()->map(fn($count, $type) => (\App\Models\SimulationSessionEvent::LABELS[$type] ?? str_replace('_', ' ', $type)).': '.$count)->implode(', ') }}">{{ $session->events->count() }} aktivitas tercatat</p>@endif
                                            @if($status === 'submitted')<p class="font-semibold text-success-700">Jawaban dikumpulkan</p>@endif
                                        </div>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(1, $program->simulations->count() + 1) }}" class="px-5 py-14 text-center text-sm font-medium text-gray-500">Belum ada peserta pada program ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
