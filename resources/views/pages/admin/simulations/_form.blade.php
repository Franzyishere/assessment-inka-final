@php
    $editing = isset($scenario);
    $initialPages = old('material_pages', $editing ? $scenario->materialPages->map(fn ($page) => [
        'id' => $page->id,
        'title' => $page->title,
        'content' => $page->content,
        'attachment_name' => $page->attachment_name,
        'is_required' => $page->is_required,
    ])->values()->all() : []);
    $typeModes = $simulationTypes->mapWithKeys(fn ($type) => [(string) $type->id => $type->delivery_mode]);
    $typeCodes = $simulationTypes->mapWithKeys(fn ($type) => [(string) $type->id => $type->code]);
@endphp

<form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.simulations.update', $scenario) : route('admin.simulations.store') }}" class="space-y-6"
    x-data="{
        selectedType: '{{ old('simulation_type_id', $scenario->simulation_type_id ?? $simulationTypes->first()?->id) }}',
        modes: @js($typeModes),
        codes: @js($typeCodes),
        pages: @js($initialPages),
        get usesPdfMaterials() { return ['multi_page_response', 'case_response'].includes(this.modes[this.selectedType]) },
        get isProblemAnalysis() { return this.modes[this.selectedType] === 'multi_page_response' },
        get isCriticalIncident() { return this.modes[this.selectedType] === 'case_response' },
        addPage() { if (!this.isProblemAnalysis || this.pages.length === 0) this.pages.push({ id: null, title: '', content: '', attachment_name: null, is_required: true }) },
        removePage(index) { this.pages.splice(index, 1) }
    }" x-init="if (isProblemAnalysis && pages.length === 0) addPage()" x-effect="if (isProblemAnalysis) { if (pages.length === 0) addPage(); if (pages.length > 1) pages = pages.slice(0, 1); }">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jenis Simulasi</label>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-800 dark:border-gray-800 dark:bg-white/5 dark:text-white/90">{{ $scenario->type->name }}@if($scenario->simulationThreePackageLabel())<span class="ml-2 text-xs font-medium text-brand-500">{{ $scenario->simulationThreePackageLabel() }}</span>@endif</div>
                @if($scenario->simulationThreeAudience())<p class="mt-1.5 text-xs text-gray-500">Digunakan untuk: {{ $scenario->simulationThreeAudience() }}</p>@endif
                <input type="hidden" name="simulation_type_id" value="{{ $scenario->simulation_type_id }}">
                <input type="hidden" name="assessment_category" value="{{ $scenario->assessment_category }}">
                <input type="hidden" name="status" value="active">
            </div>
            <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi</label><textarea name="description" rows="3" class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">{{ old('description', $scenario->description ?? '') }}</textarea></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Durasi (menit)</label><input type="number" min="1" max="1440" name="duration_minutes" value="{{ old('duration_minutes', $scenario->duration_minutes ?? '') }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90"></div>
            <div class="flex items-end"><div class="w-full rounded-lg bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Simulasi bawaan aktif</div></div>
        </div>
    </div>

    <div x-show="usesPdfMaterials" x-cloak class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-5 flex items-center justify-between gap-4"><div><h2 class="font-semibold text-gray-800 dark:text-white/90" x-text="isProblemAnalysis ? 'Materi PDF Simulasi 1' : 'Materi PDF Simulasi 3'"></h2><p class="mt-1 text-sm text-gray-500" x-text="isProblemAnalysis ? 'Unggah satu PDF uraian simulasi.' : 'Tambahkan beberapa PDF; setiap materi memiliki kolom jawaban tersendiri.'"></p></div><button x-show="isCriticalIncident" type="button" @click="addPage" class="crud-btn-soft-brand shrink-0">Tambah Materi</button></div>
        <div class="space-y-4">
            <template x-for="(page, index) in pages" :key="index">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="mb-3 flex items-center justify-between gap-3"><h3 class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="`Materi ${index + 1}`"></h3><button x-show="isCriticalIncident" type="button" @click="removePage(index)" class="crud-btn-soft-danger">Hapus</button></div>
                    <input type="hidden" :name="`material_pages[${index}][id]`" x-model="page.id" :disabled="!usesPdfMaterials">
                    <input type="hidden" :name="`material_pages[${index}][title]`" :value="`Materi ${index + 1}`" :disabled="!usesPdfMaterials">
                    <input type="hidden" :name="`material_pages[${index}][is_required]`" :value="page.is_required ? 1 : 0" :disabled="!usesPdfMaterials">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">File PDF *</label>
                    <input type="file" accept="application/pdf,.pdf" :name="`material_pages[${index}][attachment]`" :required="usesPdfMaterials && !page.attachment_name" :disabled="!usesPdfMaterials" class="block w-full rounded-lg border border-gray-300 bg-transparent p-3 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 dark:border-gray-700 dark:text-gray-300">
                    <p x-show="page.attachment_name" class="mt-2 text-xs text-success-600">File saat ini: <span x-text="page.attachment_name"></span></p>
                    <template x-if="isCriticalIncident"><div class="mt-4"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Keterangan materi (opsional)</label><textarea :name="`material_pages[${index}][content]`" x-model="page.content" rows="2" placeholder="Keterangan singkat materi" class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90"></textarea></div></template>
                    <template x-if="!isCriticalIncident"><input type="hidden" :name="`material_pages[${index}][content]`" value="" :disabled="!usesPdfMaterials"></template>
                </div>
            </template>
            <p x-show="pages.length === 0" class="rounded-lg bg-gray-50 px-4 py-6 text-center text-sm text-gray-500 dark:bg-white/[0.03]">Belum ada materi PDF.</p>
            @error('material_pages')<p class="text-sm text-error-500">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex flex-wrap justify-end gap-3"><a href="{{ route('admin.simulations.index') }}" class="crud-btn-secondary">Batal</a><button class="crud-btn-primary">Simpan Materi</button></div>
</form>
