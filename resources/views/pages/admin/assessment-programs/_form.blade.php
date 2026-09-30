@php
    $editing = isset($program);
    $startsAt = old('starts_at', isset($program) && $program->starts_at ? $program->starts_at->format('Y-m-d H:i') : '');
    $startsDate = $startsAt ? \Illuminate\Support\Carbon::parse($startsAt)->format('Y-m-d') : '';
    $startsTime = $startsAt ? \Illuminate\Support\Carbon::parse($startsAt)->format('H:i') : '';

    $endsAt = old('ends_at', isset($program) && $program->ends_at ? $program->ends_at->format('Y-m-d H:i') : '');
    $endsDate = $endsAt ? \Illuminate\Support\Carbon::parse($endsAt)->format('Y-m-d') : '';
    $endsTime = $endsAt ? \Illuminate\Support\Carbon::parse($endsAt)->format('H:i') : '';
@endphp

<form method="POST" action="{{ $editing ? route('admin.assessment-programs.update', $program) : route('admin.assessment-programs.store') }}" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Program *</label>
                <input name="name" value="{{ old('name', $program->name ?? ($editing ? '' : 'Assessment ')) }}" placeholder="Assessment ..." required class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                @error('name')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi</label>
                <textarea name="description" rows="4" class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">{{ old('description', $program->description ?? '') }}</textarea>
            </div>

            <div x-data="dateTimePicker('{{ $startsDate }}', '{{ $startsTime }}', '08:00')">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Waktu Mulai</label>
                <input type="hidden" name="starts_at" :value="combinedValue" value="{{ $startsAt }}">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input type="text"
                               x-ref="dateInput"
                               placeholder="Pilih tanggal..."
                               readonly
                               class="shadow-theme-xs h-11 w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 pr-14 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                            <button type="button"
                                    x-show="date"
                                    x-cloak
                                    @click="clearDate()"
                                    title="Hapus tanggal"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <span class="pointer-events-none text-gray-400">
                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect width="18" height="18" x="3" y="4" rx="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="relative w-32 shrink-0">
                        <input type="text"
                               x-model="time"
                               @input="formatTime($event)"
                               @blur="normalizeTime()"
                               placeholder="08:00"
                               maxlength="5"
                               title="Ketik jam format 24 jam (HH:mm)"
                               class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-8 font-mono text-center text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-gray-400">
                    <span>Pencet tanggal di kalender</span>
                    <span>Ketik jam (24 jam)</span>
                </div>
                @error('starts_at')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <div x-data="dateTimePicker('{{ $endsDate }}', '{{ $endsTime }}', '17:00')">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Waktu Selesai</label>
                <input type="hidden" name="ends_at" :value="combinedValue" value="{{ $endsAt }}">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input type="text"
                               x-ref="dateInput"
                               placeholder="Pilih tanggal..."
                               readonly
                               class="shadow-theme-xs h-11 w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 pr-14 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                            <button type="button"
                                    x-show="date"
                                    x-cloak
                                    @click="clearDate()"
                                    title="Hapus tanggal"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <span class="pointer-events-none text-gray-400">
                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect width="18" height="18" x="3" y="4" rx="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="relative w-32 shrink-0">
                        <input type="text"
                               x-model="time"
                               @input="formatTime($event)"
                               @blur="normalizeTime()"
                               placeholder="17:00"
                               maxlength="5"
                               title="Ketik jam format 24 jam (HH:mm)"
                               class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-8 font-mono text-center text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-dark-900 dark:text-white/90">
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-gray-400">
                    <span>Pencet tanggal di kalender</span>
                    <span>Ketik jam (24 jam)</span>
                </div>
                @error('ends_at')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status *</label>
                <select name="status" required class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                    @foreach (['draft' => 'Draft', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $program->status ?? 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Program berstatus Draft akan otomatis aktif saat waktu Mulai tiba.</p>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route(($program->archived_at ?? null) ? 'admin.result-archives.index' : 'admin.assessment-programs.index') }}" class="crud-btn-secondary">Batal</a>
        <button class="crud-btn-primary">{{ $editing ? 'Simpan Perubahan' : 'Buat Program' }}</button>
    </div>
</form>
