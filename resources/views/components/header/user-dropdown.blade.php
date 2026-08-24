<div class="relative" x-data="{ dropdownOpen: false }">
    <button type="button" class="flex items-center text-gray-700 dark:text-gray-400" @click="dropdownOpen = !dropdownOpen">
        <span class="mr-3 flex size-11 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </span>
        <span class="mr-1 hidden text-left lg:block">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ auth()->user()->name }}</span>
            <span class="block text-xs text-gray-500 dark:text-gray-500">{{ auth()->user()->roleLabel() }}</span>
        </span>
        <svg class="hidden size-5 transition-transform lg:block" :class="dropdownOpen && 'rotate-180'" viewBox="0 0 20 20" fill="none"><path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <div x-show="dropdownOpen" @click.outside="dropdownOpen = false" x-transition
        class="shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800">
        <div class="px-3 py-2">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ auth()->user()->name }}</span>
            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-500">{{ auth()->user()->email }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-gray-100 pt-2 dark:border-gray-800">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
                Keluar
            </button>
        </form>
    </div>
</div>
