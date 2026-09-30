@extends('layouts.app')

@section('content')
    @php
        $archiveMode = $archiveMode ?? false;
    @endphp
    <x-common.page-breadcrumb :pageTitle="$archiveMode ? 'Arsip Program Assessment' : 'Program Assessment'" />


    <div x-data="{ deleting: null, bulkConfirm: false, selected: [], eligible: @js($programs->where('status', '!==', 'active')->pluck('id')->map(fn ($id) => (int) $id)->values()) }" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $archiveMode ? 'Daftar Arsip Program' : 'Daftar Program' }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $archiveMode ? 'Program yang dipindahkan dari daftar utama. Pengaturan, peserta, dan penilaian tetap tersedia.' : 'Program assessment internal yang dikelola Admin HCGA.' }}</p>
            </div>
            @if(!$archiveMode)<a href="{{ route('admin.assessment-programs.create') }}" class="crud-btn-primary">Buat Program</a>@endif
        </div>
        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <x-common.search-form :action="route($archiveMode ? 'admin.result-archives.index' : 'admin.assessment-programs.index')" placeholder="Cari nama program..." />
            <p class="shrink-0 text-sm text-gray-500"><span class="font-semibold tabular-nums text-gray-800">{{ number_format($programs->total()) }}</span> {{ request()->filled('search') ? 'program ditemukan' : 'program' }}</p>
        </div>
        <form id="bulk-program-form" method="POST" action="{{ route('admin.assessment-programs.bulk-destroy') }}">@csrf @method('DELETE')</form>
        <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-brand-50 px-5 py-3">
            <p class="text-sm font-semibold text-brand-700"><span x-text="selected.length"></span> program dipilih</p>
            <div class="flex items-center gap-3"><button type="button" @click="selected = []" class="text-sm font-medium text-gray-600 hover:text-gray-900">Batal pilih</button><button type="button" @click="bulkConfirm = true" class="crud-btn-danger">Hapus Terpilih</button></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] divide-y divide-gray-200 dark:divide-gray-800">
                <caption class="sr-only">{{ $archiveMode ? 'Daftar arsip program assessment' : 'Daftar program assessment' }}</caption>
                <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>
                    @if(!$archiveMode)<th scope="col" class="w-12 px-5 py-3"><input type="checkbox" aria-label="Pilih semua program yang dapat dihapus" :checked="eligible.length > 0 && selected.length === eligible.length" x-effect="$el.indeterminate = selected.length > 0 && selected.length < eligible.length" :disabled="eligible.length === 0" @change="selected = $event.target.checked ? [...eligible] : []" class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 disabled:opacity-40"></th>@endif
                    @foreach (['Nama Program', 'Periode', 'Simulasi', 'Peserta', 'Status', 'Aksi'] as $heading)
                        <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500 {{ in_array($heading, ['Simulasi', 'Peserta', 'Status', 'Aksi']) ? 'text-center' : 'text-left' }}">{{ $heading }}</th>
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
                        <tr class="transition-colors hover:bg-gray-50/80" @if(!$archiveMode) :class="selected.includes({{ $program->id }}) ? 'bg-brand-50/50' : ''" @endif>
                            @if(!$archiveMode)<td class="px-5 py-4"><input form="bulk-program-form" type="checkbox" name="ids[]" value="{{ $program->id }}" x-model.number="selected" @disabled($program->status === 'active') aria-label="Pilih program {{ $program->name }}" title="{{ $program->status === 'active' ? 'Program berstatus aktif tidak dapat dihapus' : 'Pilih program' }}" class="size-4 rounded border-gray-300 text-brand-500 disabled:cursor-not-allowed disabled:opacity-40"></td>@endif
                            <td class="px-5 py-5"><a href="{{ route('admin.assessment-programs.setup.edit', $program) }}" class="block max-w-sm break-words text-sm font-semibold leading-6 text-gray-900 hover:text-brand-600">{{ $program->name }}</a></td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm">
                                <div class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1.5">
                                    <span class="text-xs text-gray-500">Mulai</span><span class="font-medium tabular-nums text-gray-800">{{ $program->starts_at?->format('d M Y · H:i') ?? 'Belum dijadwalkan' }}</span>
                                    <span class="text-xs text-gray-500">Selesai</span><span class="tabular-nums text-gray-600">{{ $program->ends_at?->format('d M Y · H:i') ?? 'Belum dijadwalkan' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-5 text-center text-sm font-semibold tabular-nums text-gray-800">{{ $program->simulations_count }}</td>
                            <td class="px-5 py-5 text-center text-sm font-semibold tabular-nums text-gray-800">{{ $program->participants_count }}</td>
                            <td class="px-5 py-5 text-center"><span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ ucfirst($program->status) }}</span></td>
                            <td class="px-5 py-4"><div class="crud-actions">
                                <a href="{{ route('admin.assessment-programs.setup.edit', $program) }}" class="crud-btn-soft-brand">Atur</a>
                                <a href="{{ route('admin.assessment-programs.edit', $program) }}" class="crud-btn-soft-neutral">Edit</a>
                                @if($archiveMode)
                                    <a href="{{ route('admin.result-archives.show', $program->id) }}" class="crud-btn-soft-neutral">Hasil</a>
                                @else
                                    <button type="button" @disabled($program->status === 'active') @click="deleting = { name: @js($program->name), action: @js(route('admin.assessment-programs.destroy', $program)) }" class="crud-btn-soft-danger crud-btn-icon disabled:cursor-not-allowed disabled:opacity-40" title="{{ $program->status === 'active' ? 'Program aktif tidak dapat dihapus' : 'Pindahkan ke arsip' }}" aria-label="Hapus program {{ $program->name }}"><svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3.75 5.5h12.5M8 3.25h4M5.75 5.5l.6 10a1.5 1.5 0 0 0 1.5 1.4h4.3a1.5 1.5 0 0 0 1.5-1.4l.6-10M8.25 8.5v5.25m3.5-5.25v5.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $archiveMode ? 6 : 7 }}" class="px-5 py-14 text-center"><p class="text-sm font-semibold text-gray-800">{{ request()->filled('search') ? 'Program tidak ditemukan.' : ($archiveMode ? 'Belum ada program yang diarsipkan.' : 'Belum ada program assessment.') }}</p><p class="mt-2 text-sm text-gray-500">{{ request()->filled('search') ? 'Gunakan kata kunci lain atau reset pencarian.' : ($archiveMode ? 'Program yang diarsipkan akan muncul di sini.' : 'Buat program untuk mulai mengatur simulasi dan peserta.') }}</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($programs->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $programs->links() }}</div>@endif

        <div x-show="deleting" x-cloak @keydown.escape.window="deleting = null" class="fixed inset-0 z-99999 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="deleting = null"></div>
            <div x-show="deleting" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Hapus Program Assessment?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Program <span class="font-medium text-gray-700 dark:text-gray-300" x-text="deleting?.name"></span> akan dipindahkan ke Arsip Program Assessment. Akses dan penilaian asesor tidak berubah. Program aktif tidak dapat dipindahkan.</p>
                <div class="mt-6 flex flex-wrap justify-end gap-3"><button type="button" @click="deleting = null" class="crud-btn-secondary">Batal</button><form method="POST" :action="deleting?.action"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="crud-btn-danger">Ya, Hapus</button></form></div>
            </div>
        </div>
        <div x-show="bulkConfirm" x-cloak @keydown.escape.window="bulkConfirm = false" class="fixed inset-0 z-99999 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="bulkConfirm = false"></div>
            <div x-show="bulkConfirm" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl">
                <h3 class="text-lg font-semibold text-gray-900">Hapus Program Terpilih?</h3>
                <p class="mt-2 text-sm text-gray-500"><span class="font-semibold" x-text="selected.length"></span> program akan dipindahkan ke Arsip Program Assessment. Akses dan penilaian asesor tidak berubah.</p>
                <div class="mt-6 flex justify-end gap-3"><button type="button" @click="bulkConfirm = false" class="crud-btn-secondary">Batal</button><button type="submit" form="bulk-program-form" class="crud-btn-danger">Ya, Hapus</button></div>
            </div>
        </div>
    </div>
@endsection
