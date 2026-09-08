@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Manajemen Pengguna" />
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"><div><h2 class="font-semibold text-gray-800 dark:text-white/90">Pengguna Internal</h2><p class="mt-1 text-sm text-gray-500">Super Admin, Admin HCGA, dan Asesor.</p></div><div class="flex flex-wrap items-center gap-2"><a href="{{ route('admin.participants.index') }}" class="crud-btn-secondary">Peserta Assessment</a><a href="{{ route('super-admin.users.create') }}" class="crud-btn-primary shrink-0">Tambah Pengguna</a></div></div>
    <div class="border-b border-gray-200 bg-gray-50/60 px-5 py-3"><x-common.search-form :action="route('super-admin.users.index')" placeholder="Cari nama, email, atau role..." /></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800"><thead class="bg-gray-50 dark:bg-gray-900/50"><tr>@foreach(['Pengguna','Role','Dibuat','Aksi'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">@foreach($users as $user)<tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/[0.02]"><td class="px-5 py-4"><div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</div><div class="text-xs text-gray-500">{{ $user->email }}</div></td><td class="px-5 py-4"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs text-brand-600">{{ $user->roleLabel() }}</span></td><td class="px-5 py-4 text-sm text-gray-500">{{ $user->created_at->format('d M Y') }}</td><td class="px-5 py-4"><div class="crud-actions"><a href="{{ route('super-admin.users.edit', $user) }}" class="crud-btn-soft-neutral">Edit</a>
<form method="POST" action="{{ route('super-admin.users.destroy', $user) }}" x-data
    @submit.prevent="$dispatch('confirm-dialog', { title: 'Hapus Pengguna?', message: @js('Akun '.$user->name.' akan dihapus permanen. Pengguna yang terkait data assessment tidak dapat dihapus.'), confirmLabel: 'Ya, Hapus', tone: 'danger', onConfirm: () => $el.submit() })">
    @csrf @method('DELETE')
    <button type="submit" @disabled(auth()->id() === $user->id) class="crud-btn-soft-danger crud-btn-icon disabled:cursor-not-allowed disabled:opacity-40" title="{{ auth()->id() === $user->id ? 'Akun sendiri tidak dapat dihapus' : 'Hapus pengguna' }}" aria-label="Hapus pengguna {{ $user->name }}">
        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3.75 5.5h12.5M8 3.25h4M5.75 5.5l.6 10a1.5 1.5 0 0 0 1.5 1.4h4.3a1.5 1.5 0 0 0 1.5-1.4l.6-10M8.25 8.5v5.25m3.5-5.25v5.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
</form></div></td></tr>@endforeach</tbody></table></div>
    @if($users->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $users->links() }}</div>@endif
</div>
@endsection
