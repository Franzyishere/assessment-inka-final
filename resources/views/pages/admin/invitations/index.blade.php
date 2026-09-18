@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Undangan Assessment" />
<div class="rounded-2xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 p-5"><x-common.search-form :action="route('admin.invitations.index')" placeholder="Cari program assessment…" /></div>
    <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-700"><tr><th class="p-4">Program</th><th class="p-4">Hari pelaksanaan</th><th class="p-4">Undangan / peserta</th><th class="p-4 text-center">Aksi</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($programs as $program)
            <tr><td class="p-4 font-semibold text-gray-900">{{ $program->name }}</td><td class="p-4">{{ $program->starts_at?->format('d M Y') ?? 'Belum dijadwalkan' }}</td><td class="p-4">{{ $program->invited_count }} / {{ $program->participants_count }}</td><td class="p-4"><div class="crud-actions"><a href="{{ route('admin.invitations.show', $program) }}" class="crud-btn-soft-neutral">Kelola Undangan</a></div></td></tr>
        @empty
            <tr><td colspan="4" class="p-8 text-center text-gray-500">Tidak ada program.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="p-5">{{ $programs->links() }}</div>
</div>
@endsection
