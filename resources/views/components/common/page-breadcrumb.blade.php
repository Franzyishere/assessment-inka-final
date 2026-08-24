@props(['pageTitle' => 'Page'])

<div class="relative mb-6 flex flex-wrap items-center justify-between gap-3 overflow-hidden rounded-2xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 py-4 shadow-theme-md sm:px-6">
    <div class="pointer-events-none absolute -right-10 -top-16 size-40 rounded-full bg-white/10"></div>
    <h2 class="relative text-xl font-semibold text-white">
        {{ $pageTitle }}
    </h2>
    <nav class="relative">
        <ol class="flex items-center gap-1.5">
            <li>
                <a
                    class="inline-flex items-center gap-1.5 text-sm text-white/70 hover:text-white"
                    href="{{ url('/') }}"
                >
                    Home
                    <svg
                        class="stroke-current"
                        width="17"
                        height="16"
                        viewBox="0 0 17 16"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                            stroke=""
                            stroke-width="1.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </a>
            </li>
            <li class="text-sm font-medium text-white">
                {{ $pageTitle }}
            </li>
        </ol>
    </nav>
</div>
