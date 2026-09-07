@extends('layouts.app')

@section('content')
@php
    $actionLabel = fn (string $action) => str($action)->replace('.', ' ')->headline();
    $subjectLabel = fn (?string $subject) => $subject ? class_basename($subject) : 'Sistem';
    $metadataText = fn ($metadata) => filled($metadata)
        ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        : null;
@endphp

<x-common.page-breadcrumb pageTitle="Audit Log" />

<section class="min-w-0 max-w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
    <header class="flex flex-col gap-4 border-b border-gray-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <div>
            <h2 class="font-semibold text-gray-800 dark:text-white/90">Aktivitas Sistem</h2>
            <p class="mt-1 text-sm text-gray-500">Riwayat aktivitas penting pengguna dan perubahan data.</p>
        </div>
        <form data-live-search method="GET" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <label class="relative min-w-56"><span class="sr-only">Cari audit log</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari pelaku atau aktivitas..." class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none focus:border-brand-400"></label>
            <label for="audit-action" class="sr-only">Filter aktivitas</label>
            <select id="audit-action" name="action" onchange="this.form.requestSubmit()" class="h-10 w-full min-w-52 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10 sm:w-auto dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <option value="">Semua aktivitas</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $actionLabel($action) }}</option>
                @endforeach
            </select>
            <button class="crud-btn-secondary">Cari</button>
            @if(request()->filled('search') || request()->filled('action'))<a href="{{ route('super-admin.audit-logs.index') }}" class="self-center text-xs font-semibold text-gray-500">Reset</a>@endif
        </form>
    </header>

    <div class="hidden w-full max-w-full overscroll-x-contain overflow-x-auto lg:block">
        <table class="w-full min-w-[1080px] table-fixed divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="w-44 px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Waktu</th>
                    <th class="w-56 px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Pelaku</th>
                    <th class="w-48 px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Aktivitas</th>
                    <th class="w-44 px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Target</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($logs as $log)
                    @php($detail = $metadataText($log->metadata))
                    <tr class="align-top transition hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                        <td class="whitespace-nowrap px-5 py-4"><p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $log->occurred_at?->format('d M Y') ?? '-' }}</p><p class="mt-0.5 text-xs text-gray-500">{{ $log->occurred_at?->format('H:i:s') ?? '-' }} WIB</p></td>
                        <td class="px-5 py-4"><p class="truncate text-sm font-medium text-gray-800 dark:text-white/90" title="{{ $log->user?->name ?? 'Sistem' }}">{{ $log->user?->name ?? 'Sistem' }}</p><p class="mt-0.5 truncate text-xs text-gray-500" title="{{ $log->user?->email ?? '-' }}">{{ $log->user?->email ?? '-' }}</p></td>
                        <td class="px-5 py-4"><span class="inline-flex max-w-full rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ $actionLabel($log->action) }}</span><p class="mt-1.5 truncate font-mono text-[10px] text-gray-400" title="{{ $log->action }}">{{ $log->action }}</p></td>
                        <td class="px-5 py-4"><p class="text-sm text-gray-700 dark:text-gray-300">{{ $subjectLabel($log->subject_type) }}</p>@if($log->subject_id)<p class="mt-0.5 text-xs text-gray-500">ID #{{ $log->subject_id }}</p>@endif</td>
                        <td class="px-5 py-4">
                            <p class="text-xs text-gray-500">IP: {{ $log->ip_address ?? '-' }}</p>
                            @if($detail || $log->user_agent)
                                <details class="group mt-2 max-w-md">
                                    <summary class="cursor-pointer list-none text-xs font-medium text-brand-600 hover:text-brand-700">Lihat metadata <span class="inline-block transition group-open:rotate-180">⌄</span></summary>
                                    <div class="mt-2 max-h-44 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                        @if($detail)<pre class="whitespace-pre-wrap break-words font-mono text-[11px] leading-5 text-gray-600 dark:text-gray-300">{{ $detail }}</pre>@endif
                                        @if($log->user_agent)<p class="mt-2 break-words border-t border-gray-200 pt-2 text-[11px] leading-5 text-gray-500 dark:border-gray-700">{{ $log->user_agent }}</p>@endif
                                    </div>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-gray-500">Belum ada aktivitas audit.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="divide-y divide-gray-100 lg:hidden dark:divide-gray-800">
        @forelse($logs as $log)
            @php($detail = $metadataText($log->metadata))
            <article class="p-5">
                <div class="flex items-start justify-between gap-3"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ $actionLabel($log->action) }}</span><time class="shrink-0 text-right text-xs text-gray-500">{{ $log->occurred_at?->format('d M Y') ?? '-' }}<br>{{ $log->occurred_at?->format('H:i:s') ?? '-' }} WIB</time></div>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm"><div><p class="text-xs text-gray-400">Pelaku</p><p class="mt-1 break-words font-medium text-gray-800 dark:text-white/90">{{ $log->user?->name ?? 'Sistem' }}</p><p class="break-all text-xs text-gray-500">{{ $log->user?->email ?? '-' }}</p></div><div><p class="text-xs text-gray-400">Target</p><p class="mt-1 text-gray-700 dark:text-gray-300">{{ $subjectLabel($log->subject_type) }} @if($log->subject_id)#{{ $log->subject_id }}@endif</p><p class="mt-1 text-xs text-gray-500">IP: {{ $log->ip_address ?? '-' }}</p></div></div>
                @if($detail || $log->user_agent)<details class="group mt-4"><summary class="cursor-pointer list-none text-xs font-medium text-brand-600">Lihat metadata <span class="inline-block transition group-open:rotate-180">⌄</span></summary><div class="mt-2 max-h-44 overflow-auto rounded-lg bg-gray-50 p-3 dark:bg-gray-900">@if($detail)<pre class="whitespace-pre-wrap break-words font-mono text-[11px] leading-5 text-gray-600 dark:text-gray-300">{{ $detail }}</pre>@endif @if($log->user_agent)<p class="mt-2 break-words border-t border-gray-200 pt-2 text-[11px] leading-5 text-gray-500 dark:border-gray-700">{{ $log->user_agent }}</p>@endif</div></details>@endif
            </article>
        @empty
            <div class="px-5 py-14 text-center text-sm text-gray-500">Belum ada aktivitas audit.</div>
        @endforelse
    </div>

    @if($logs->hasPages())
        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $logs->links() }}</div>
    @endif
</section>
@endsection
