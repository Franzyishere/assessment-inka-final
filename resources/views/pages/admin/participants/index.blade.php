@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Peserta Assessment" />

<div x-data="{ deleting: null }" class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-gray-200 bg-gradient-to-r from-brand-700 to-brand-500 px-5 py-5 text-white md:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wider text-white/70">Persiapan Integrasi</p><h2 class="mt-1 text-xl font-semibold">Sumber Data HRIS INKA</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">Identitas peserta disiapkan untuk dicocokkan melalui Nomor Pegawai/NIPP. Sinkronisasi baru dapat diaktifkan setelah endpoint, metode autentikasi, dan format data HRIS diterima.</p></div>
                <div class="flex shrink-0 items-center gap-3 rounded-xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm"><span class="size-2.5 rounded-full {{ $hrisConfigured ? 'bg-success-300' : 'bg-warning-300' }}"></span><div><p class="text-xs text-white/70">Status koneksi</p><p class="text-sm font-semibold">{{ $hrisConfigured ? 'Konfigurasi tersedia' : 'Belum dikonfigurasi' }}</p></div></div>
            </div>
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-3 md:p-6">
            @foreach([['Seluruh peserta', $participantMetrics['total']], ['Data HRIS', $participantMetrics['hris']], ['Input manual', $participantMetrics['manual']]] as [$label, $value])
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-4"><p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ $value }}</p></div>
            @endforeach
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
        <header class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 lg:flex-row lg:items-end lg:justify-between"><div><h2 class="font-semibold text-gray-900">Akun Peserta Assessment</h2><p class="mt-1 text-sm text-gray-500">Kelola akun manual dan data peserta yang nantinya berasal dari HRIS.</p></div><a href="{{ route('admin.participants.create') }}" class="crud-btn-primary shrink-0">Tambah Peserta Manual</a></header>
            <form data-live-search method="GET" action="{{ route('admin.participants.index') }}" class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-end">
            <div class="flex-1"><label for="participant-search" class="mb-1.5 block text-xs font-medium text-gray-600">Cari peserta</label><input id="participant-search" name="search" value="{{ $search }}" type="search" placeholder="Nama, email, atau Nomor Pegawai/NIPP" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 focus:border-brand-400 focus:outline-none"></div>
            <div class="sm:w-48"><label for="source-filter" class="mb-1.5 block text-xs font-medium text-gray-600">Sumber data</label><select id="source-filter" name="source" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 focus:border-brand-400 focus:outline-none"><option value="">Semua sumber</option><option value="hris" @selected($source === 'hris')>HRIS</option><option value="manual" @selected($source === 'manual')>Manual</option></select></div>
            <button class="crud-btn-secondary h-11">Terapkan</button>@if($search || $source)<a href="{{ route('admin.participants.index') }}" class="inline-flex h-11 items-center text-sm font-medium text-gray-500 hover:text-gray-800">Reset</a>@endif
        </form>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>@foreach(['Peserta', 'Nomor Pegawai/NIPP', 'Sumber Data', 'Program Diikuti', 'Sinkronisasi', 'Aksi'] as $heading)<th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">
            @forelse($participants as $participant)
                <tr class="transition-colors hover:bg-gray-50/70">
                    <td class="px-5 py-4"><div class="text-sm font-medium text-gray-900">{{ $participant->name }}</div><div class="text-xs text-gray-500">{{ $participant->email }}</div>@if($participant->hris_employee_id)<div class="mt-1 text-[11px] text-gray-400">HRIS ID: {{ $participant->hris_employee_id }}</div>@endif</td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-700">{{ $participant->employee_number ?: '-' }}</td>
                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $participant->identity_source === 'hris' ? 'bg-success-50 text-success-700' : 'bg-gray-100 text-gray-600' }}">{{ $participant->identity_source === 'hris' ? 'HRIS' : 'Manual' }}</span></td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $participant->assessment_participations_count }}</td>
                    <td class="whitespace-nowrap px-5 py-4">@if($participant->identity_source === 'hris' && $participant->hris_synced_at)<p class="text-xs font-medium text-success-700">Tersinkronisasi</p><p class="mt-1 text-xs text-gray-500">{{ $participant->hris_synced_at->format('d M Y, H:i') }}</p>@elseif($participant->identity_source === 'hris')<span class="text-xs font-medium text-warning-700">Menunggu sinkronisasi</span>@else<span class="text-xs text-gray-400">Tidak berlaku</span>@endif</td>
                    <td class="px-5 py-4"><div class="crud-actions"><a href="{{ route('admin.participants.edit', $participant) }}" class="crud-btn-soft-neutral">Edit</a><button type="button" @click="deleting = { name: @js($participant->name), action: @js(route('admin.participants.destroy', $participant)) }" class="crud-btn-soft-danger crud-btn-icon" title="Hapus peserta" aria-label="Hapus peserta {{ $participant->name }}"><svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3.75 5.5h12.5M8 3.25h4M5.75 5.5l.6 10a1.5 1.5 0 0 0 1.5 1.4h4.3a1.5 1.5 0 0 0 1.5-1.4l.6-10M8.25 8.5v5.25m3.5-5.25v5.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button></div></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-14 text-center"><h3 class="font-medium text-gray-800">Data peserta tidak ditemukan</h3><p class="mt-1 text-sm text-gray-500">Periksa kembali pencarian atau filter sumber data.</p></td></tr>
            @endforelse
        </tbody></table></div>
        @if($participants->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $participants->links() }}</div>@endif
    </section>
    <div x-show="deleting" x-cloak @keydown.escape.window="deleting = null" class="fixed inset-0 z-99999 flex items-center justify-center p-4"><div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="deleting = null"></div><div x-show="deleting" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl"><h3 class="text-lg font-semibold text-gray-800">Hapus Peserta Assessment?</h3><p class="mt-2 text-sm text-gray-500">Akun <span class="font-medium text-gray-700" x-text="deleting?.name"></span> akan dihapus permanen. Peserta yang sudah masuk ke program tidak dapat dihapus.</p><div class="mt-6 flex flex-wrap justify-end gap-3"><button type="button" @click="deleting = null" class="crud-btn-secondary">Batal</button><form method="POST" :action="deleting?.action"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="crud-btn-danger">Ya, Hapus</button></form></div></div></div>
</div>
@endsection
