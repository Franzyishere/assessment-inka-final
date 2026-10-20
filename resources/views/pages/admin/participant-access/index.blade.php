@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Akses Peserta" />
<x-common.search-form :action="route('admin.participant-access.index')" placeholder="Cari program..." />
<div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:bg-gray-900">
    <table class="w-full text-left text-sm">
        <thead><tr><th class="p-4">Program</th><th class="p-4">Jadwal</th><th class="p-4">Peserta</th><th class="p-4 text-center">Aksi</th></tr></thead>
        <tbody>@forelse($programs as $program)
            <tr class="border-t border-gray-200"><td class="p-4">{{ $program->name }}</td><td class="p-4">{{ $program->starts_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td><td class="p-4">{{ $program->participants_count }}</td><td class="p-4"><div class="crud-actions"><a href="{{ route('admin.participant-access.show', $program) }}" class="crud-btn-soft-neutral">Kelola Akses</a></div></td></tr>
        @empty<tr><td colspan="4" class="p-6 text-center">Tidak ada program.</td></tr>@endforelse</tbody>
    </table>
</div>
<div class="mt-5">{{ $programs->links() }}</div>
@endsection
