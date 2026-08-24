@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Manajemen Pengguna" />
@if(session('success'))<div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>@endif
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"><div><h2 class="font-semibold text-gray-800 dark:text-white/90">Seluruh Pengguna</h2><p class="mt-1 text-sm text-gray-500">Kelola akun dan role sistem.</p></div><a href="{{ route('super-admin.users.create') }}" class="crud-btn-primary shrink-0">Tambah Pengguna</a></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800"><thead class="bg-gray-50 dark:bg-gray-900/50"><tr>@foreach(['Pengguna','Role','Dibuat','Aksi'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">@foreach($users as $user)<tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/[0.02]"><td class="px-5 py-4"><div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</div><div class="text-xs text-gray-500">{{ $user->email }}</div></td><td class="px-5 py-4"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs text-brand-600">{{ $user->roleLabel() }}</span></td><td class="px-5 py-4 text-sm text-gray-500">{{ $user->created_at->format('d M Y') }}</td><td class="px-5 py-4"><a href="{{ route('super-admin.users.edit', $user) }}" class="crud-btn-soft-neutral">Edit</a></td></tr>@endforeach</tbody></table></div>
    @if($users->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $users->links() }}</div>@endif
</div>
@endsection
