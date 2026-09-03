@php($batch = $batch ?? null)
<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700">Nama Batch <span class="text-error-500">*</span></label>
        <input name="name" value="{{ old('name', $batch?->name) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10" placeholder="Contoh: Rekrutmen Eksternal September 2026">
        @error('name')<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700">Mulai</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $batch?->starts_at?->format('Y-m-d\TH:i')) }}" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10">
        @error('starts_at')<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700">Selesai</label>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $batch?->ends_at?->format('Y-m-d\TH:i')) }}" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10">
        @error('ends_at')<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700">Status <span class="text-error-500">*</span></label>
        <select name="status" required class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10">
            @foreach(['draft' => 'Draft', 'scheduled' => 'Terjadwal', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $batch?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700">Deskripsi</label>
        <textarea name="description" rows="4" maxlength="5000" class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10" placeholder="Informasi internal mengenai batch rekrutmen">{{ old('description', $batch?->description) }}</textarea>
        @error('description')<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
    </div>
</div>
