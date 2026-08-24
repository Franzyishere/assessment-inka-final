@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Program Assessment" />

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
    @endif

    <div x-data="{ deleting: null }" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h2 class="font-semibold text-gray-800 dark:text-white/90">Daftar Program</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Program assessment internal yang dikelola Admin HCGA.</p>
            </div>
            <a href="{{ route('admin.assessment-programs.create') }}" class="crud-btn-primary">Buat Program</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>
                    @foreach (['Nama Program', 'Periode', 'Simulasi', 'Peserta', 'Status', 'Aksi'] as $heading)
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ $heading }}</th>
                    @endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($programs as $program)
                        @php
                            $statusClass = match ($program->status) {
                                'active' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                                'completed' => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                                'cancelled' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
                                default => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-4"><div class="font-medium text-gray-800 dark:text-white/90">{{ $program->name }}</div></td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $program->starts_at?->format('d M Y H:i') ?? '-' }}<br>{{ $program->ends_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $program->simulations_count }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $program->participants_count }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">{{ ucfirst($program->status) }}</span></td>
                            <td class="px-5 py-4"><div class="crud-actions"><a href="{{ route('admin.assessment-programs.setup.edit', $program) }}" class="crud-btn-soft-brand">Atur</a><a href="{{ route('admin.assessment-programs.edit', $program) }}" class="crud-btn-soft-neutral">Edit</a><button type="button" @click="deleting = { name: @js($program->name), action: @js(route('admin.assessment-programs.destroy', $program)) }" class="crud-btn-soft-danger">Hapus</button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada program assessment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($programs->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $programs->links() }}</div>@endif

        <div x-show="deleting" x-cloak @keydown.escape.window="deleting = null" class="fixed inset-0 z-99999 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="deleting = null"></div>
            <div x-show="deleting" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Hapus Program Assessment?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Program <span class="font-medium text-gray-700 dark:text-gray-300" x-text="deleting?.name"></span> akan dihapus permanen. Hanya program draft tanpa riwayat pengerjaan yang dapat dihapus.</p>
                <div class="mt-6 flex flex-wrap justify-end gap-3"><button type="button" @click="deleting = null" class="crud-btn-secondary">Batal</button><form method="POST" :action="deleting?.action"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="crud-btn-danger">Ya, Hapus</button></form></div>
            </div>
        </div>
    </div>
@endsection
