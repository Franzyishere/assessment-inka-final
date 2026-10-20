@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Akses Peserta" />
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h2 class="text-xl font-semibold">{{ $program->name }}</h2><p class="mt-1 text-sm text-gray-500">Bagikan kode secara pribadi setelah memeriksa identitas peserta. Kode berlaku untuk satu kali login pada jadwal program.</p></div>
    <a href="{{ route('admin.participant-access.index') }}" class="crud-btn-secondary">Kembali</a>
</div>
<x-common.search-form :action="route('admin.participant-access.show', $program)" placeholder="Cari nama atau NIP..." />
<div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:bg-gray-900">
<table class="w-full text-left text-sm">
    <thead><tr><th class="p-4">Peserta / NIP</th><th class="p-4">Kode akses</th><th class="p-4">Status kode</th><th class="p-4 text-center">Aksi</th></tr></thead>
    <tbody>@forelse($participants as $participant)
        @php($access = $participant->accessCode)
        <tr class="border-t border-gray-200">
            <td class="p-4"><p class="font-semibold">{{ $participant->user->name }}</p><p>{{ $participant->user->employee_number ?: 'NIP belum diisi — lengkapi data peserta' }}</p></td>
            <td class="p-4">
                @if($access && !$access->used_at && !$access->revoked_at)
                    <code class="select-all whitespace-nowrap font-mono text-base font-semibold tracking-wider text-gray-900 dark:text-white">{{ $access->secret }}</code>
                @else
                    <span class="text-gray-400">—</span>
                @endif
            </td>
            <td class="p-4">{{ !$access ? 'Belum dibuat' : ($access->revoked_at ? 'Dicabut' : ($access->used_at ? 'Sudah dipakai' : 'Siap dibagikan')) }}</td>
            <td class="p-4"><div class="crud-actions flex-wrap">
                <form method="POST" action="{{ route('admin.participant-access.manage', [$program, $participant]) }}" @if($access) x-data @submit.prevent="$dispatch('confirm-dialog', { message: 'Ganti kode dan akhiri sesi login peserta sebelumnya?', onConfirm: () => $el.submit() })" @endif>@csrf<input type="hidden" name="action" value="{{ $access ? 'replace' : 'issue' }}"><button class="crud-btn-soft-neutral">{{ $access ? 'Ganti Kode' : 'Buat Kode' }}</button></form>
                @if($access && !$access->revoked_at)
                    <form method="POST" action="{{ route('admin.participant-access.manage', [$program, $participant]) }}" x-data @submit.prevent="$dispatch('confirm-dialog', { message: 'Cabut akses peserta? Jawaban tetap disimpan.', tone: 'danger', onConfirm: () => $el.submit() })">@csrf<input type="hidden" name="action" value="revoke"><button class="crud-btn-secondary text-error-600">Cabut Akses</button></form>
                @endif
            </div></td>
        </tr>
    @empty<tr><td colspan="4" class="p-6 text-center">Peserta tidak ditemukan.</td></tr>@endforelse</tbody>
</table>
</div>
<div class="mt-5">{{ $participants->links() }}</div>
@endsection
