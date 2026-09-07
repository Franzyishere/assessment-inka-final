@php($searchMenus = \App\Helpers\MenuHelper::getMainNavItems())
<div class="relative w-full xl:max-w-md" x-data="{ query: '', open: false, menus: @js($searchMenus), get results() { return this.menus.filter(item => item.name.toLocaleLowerCase().includes(this.query.trim().toLocaleLowerCase())); } }"
    @click.outside="open = false" @keydown.escape="open = false"
    @keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); $refs.search.focus(); open = true; }">
    <form @submit.prevent="if (results.length) window.location.assign(results[0].path)">
        <input x-ref="search" type="search" x-model="query" @focus="open = true" @input="open = true" aria-label="Cari menu" :aria-expanded="open" aria-controls="header-search-results" autocomplete="off" placeholder="Cari menu assessment..."
            class="h-11 w-full rounded-lg border border-gray-200 bg-white px-4 pr-20 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-100">
        <button type="button" @click="$refs.search.focus(); open = true" class="absolute right-3 top-3 text-xs text-gray-500" aria-label="Fokus pencarian">Ctrl K</button>
    </form>
    <div id="header-search-results" x-show="open" x-cloak class="absolute left-0 right-0 top-full z-50 mt-2 max-h-80 overflow-y-auto rounded-xl border border-gray-200 bg-white p-2 shadow-lg">
        <template x-for="item in results" :key="item.path"><a :href="item.path" x-text="item.name" class="block rounded-lg px-3 py-3 text-sm text-gray-800 hover:bg-brand-50 hover:text-brand-700"></a></template>
        <p x-show="!results.length" class="p-3 text-sm text-gray-500">Menu tidak ditemukan.</p>
    </div>
</div>
