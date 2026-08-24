@php($editing = isset($program))
<form method="POST" action="{{ $editing ? route('admin.assessment-programs.update', $program) : route('admin.assessment-programs.store') }}" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Program *</label>
                <input name="name" value="{{ old('name', $program->name ?? '') }}" required class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                @error('name')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi</label>
                <textarea name="description" rows="4" class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">{{ old('description', $program->description ?? '') }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Mulai</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($program) && $program->starts_at ? $program->starts_at->format('Y-m-d\TH:i') : '') }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Selesai</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', isset($program) && $program->ends_at ? $program->ends_at->format('Y-m-d\TH:i') : '') }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                @error('ends_at')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status *</label>
                <select name="status" required class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                    @foreach (['draft' => 'Draft', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $program->status ?? 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.assessment-programs.index') }}" class="crud-btn-secondary">Batal</a>
        <button class="crud-btn-primary">{{ $editing ? 'Simpan Perubahan' : 'Buat Program' }}</button>
    </div>
</form>
