<div class="relative" x-data="{
    open: false, items: [], loading: false, error: false,
    async refresh() {
        if (this.loading) return;
        this.loading = true; this.error = false;
        try {
            const response = await fetch(@js(route('header.notifications')), { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error();
            this.items = (await response.json()).items;
        } catch { this.error = true; }
        finally { this.loading = false; }
    }
}" x-init="refresh()" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" @click="open = !open; if (open) refresh()" :aria-expanded="open" aria-label="Notifikasi assessment"
        class="relative flex size-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 hover:bg-gray-100">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
        <span x-show="items.length && !error" x-cloak class="absolute right-0 top-0 size-2 rounded-full bg-brand-500"></span>
    </button>
    <div x-show="open" x-cloak x-transition class="fixed left-3 right-3 top-40 rounded-2xl border border-gray-200 bg-white p-4 shadow-lg sm:absolute sm:left-auto sm:right-0 sm:top-full sm:mt-3 sm:w-80">
        <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3">
            <h2 class="font-semibold text-gray-900">Notifikasi Assessment</h2>
            <button type="button" @click="refresh()" :disabled="loading" class="text-xs font-semibold text-brand-600 disabled:opacity-50">Refresh</button>
        </div>
        <p class="mb-2 text-xs text-gray-500">Ringkasan status saat ini</p>
        <p x-show="loading" role="status" class="py-3 text-sm text-gray-500">Memuat notifikasi...</p>
        <p x-show="error" role="alert" class="py-3 text-sm text-error-600">Notifikasi gagal dimuat. Coba refresh kembali.</p>
        <div x-show="!loading && !error" class="max-h-72 overflow-y-auto">
            <template x-for="item in items" :key="item.url"><a :href="item.url" x-text="item.title" class="block rounded-lg p-3 text-sm text-gray-800 hover:bg-brand-50"></a></template>
            <p x-show="!items.length" class="py-5 text-center text-sm text-gray-500">Belum ada notifikasi.</p>
        </div>
    </div>
</div>
