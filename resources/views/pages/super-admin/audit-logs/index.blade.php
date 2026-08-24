@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Audit Log" />
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"><div><h2 class="font-semibold text-gray-800 dark:text-white/90">Aktivitas Sistem</h2><p class="mt-1 text-sm text-gray-500">Jejak kejadian penting yang dilakukan pengguna.</p></div><form method="GET"><select name="action" onchange="this.form.submit()" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300"><option value="">Semua aktivitas</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach</select></form></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>@foreach(['Waktu','Pelaku','Aktivitas','Target','IP & Metadata'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">@forelse($logs as $log)<tr>
            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">{{ $log->occurred_at->format('d M Y H:i:s') }}</td>
            <td class="px-5 py-4"><div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $log->user?->name ?? 'Sistem' }}</div><div class="text-xs text-gray-500">{{ $log->user?->email ?? '-' }}</div></td>
            <td class="px-5 py-4"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600">{{ $log->action }}</span></td>
            <td class="px-5 py-4 text-sm text-gray-500">{{ $log->subject_type ?? '-' }} @if($log->subject_id)#{{ $log->subject_id }}@endif</td>
            <td class="px-5 py-4"><div class="text-xs text-gray-500">{{ $log->ip_address ?? '-' }}</div>@if($log->metadata)<div class="mt-1 max-w-xs break-words text-xs text-gray-400">{{ collect($log->metadata)->map(fn($value, $key) => $key.': '.(is_array($value) ? implode(', ', $value) : $value))->implode(' · ') }}</div>@endif</td>
        </tr>@empty<tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada aktivitas audit.</td></tr>@endforelse</tbody>
    </table></div>
    @if($logs->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $logs->links() }}</div>@endif
</div>
@endsection
