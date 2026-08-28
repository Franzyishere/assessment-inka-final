@extends('layouts.fullscreen-layout')

@section('content')
<div class="relative min-h-screen bg-white dark:bg-gray-900">
    <div class="relative flex min-h-screen flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-7 flex justify-center md:hidden">
                <div class="flex w-full max-w-[280px] items-center justify-center overflow-hidden px-4">
                    <video autoplay muted loop playsinline preload="auto" disablepictureinpicture
                        x-init="$nextTick(() => $el.play().catch(() => {}))"
                        x-on:canplay="$el.play().catch(() => {})"
                        poster="{{ asset('images/logo/logo-inka-full.svg') }}"
                        class="h-auto w-full object-contain" aria-label="Logo animasi PT INKA">
                        <source src="{{ asset('images/logo/logo-inka-motion.mp4') }}?v={{ filemtime(public_path('images/logo/logo-inka-motion.mp4')) }}" type="video/mp4">
                        <img src="{{ asset('images/logo/logo-inka-full.svg') }}" alt="Logo PT INKA" class="h-auto w-full object-contain">
                    </video>
                </div>
            </div>
            <div class="mb-8">
                <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">Masuk</h1>
                <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">Masuk ke gerbang layanan talent management PT INKA menggunakan akun yang telah terdaftar.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email<span class="text-error-500">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@inka.co.id"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Password<span class="text-error-500">*</span></label>
                        <div x-data="{ showPassword: false }" class="relative">
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan password"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            <button type="button" @click="showPassword = !showPassword" class="absolute top-1/2 right-4 -translate-y-1/2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Lihat</button>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-700 dark:text-gray-400">
                        <input type="checkbox" name="remember" value="1" class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        Ingat saya
                    </label>

                    <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                        Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="absolute inset-y-0 right-0 hidden w-1/2 items-center justify-center overflow-hidden bg-gradient-to-b from-brand-950 via-brand-800 to-brand-600 lg:flex">
        <x-common.common-grid-shape />
        <div class="pointer-events-none absolute -right-28 -top-28 size-80 rounded-full border border-white/10"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-24 size-96 rounded-full bg-white/5 blur-2xl"></div>

        <div class="z-1 w-full max-w-lg px-10">
            <div class="mb-7 flex h-[110px] items-center justify-center overflow-hidden rounded-2xl bg-white px-5 shadow-theme-lg" style="width: min(100%, 340px)">
                <video autoplay muted loop playsinline preload="metadata" disablepictureinpicture
                    poster="{{ asset('images/logo/logo-inka-full.svg') }}"
                    class="h-full w-full object-contain" aria-label="Logo animasi PT INKA">
                    <source src="{{ asset('images/logo/logo-inka-motion.mp4') }}?v={{ filemtime(public_path('images/logo/logo-inka-motion.mp4')) }}" type="video/mp4" media="(min-width: 1024px)">
                    <img src="{{ asset('images/logo/logo-inka-full.svg') }}" alt="Logo PT INKA"
                        class="h-auto w-full object-contain">
                </video>
            </div>

            <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-white/80">HUMAN CAPITAL & GENERAL AFFAIRS DIGITAL</span>
            <h2 class="mt-5 max-w-md text-3xl font-bold leading-tight text-white xl:text-3xl">INKA Talent Management System</h2>
            <p class="mt-4 max-w-md text-sm leading-6 text-white/70 xl:text-base xl:leading-7">Satu akun untuk mengakses layanan Assessment dan Recruitment sesuai peran serta penugasan Anda.</p>

            <div class="mt-7 flex flex-wrap gap-3">
                <div class="flex items-center gap-2.5 rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-white/15 text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 4h8m-8 4h8M7 2h10a2 2 0 0 1 2 2v17l-7-3-7 3V4a2 2 0 0 1 2-2Z"/></svg></span>
                    <p class="text-sm font-semibold text-white">Assessment</p>
                </div>
                <div class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-white/10 text-white/80"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 7V4h8v3m-11 4h14m-1-4H6a2 2 0 0 0-2 2v10h16V9a2 2 0 0 0-2-2Z"/></svg></span>
                    <div><p class="text-sm font-semibold text-white">Recruitment</p><p class="text-[10px] font-medium text-white/50">Coming Soon</p></div>
                </div>
            </div>

            <p class="mt-6 flex items-center gap-2 text-xs text-white/50">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2m-11 0h12v10H6V10Z"/></svg>
                Akses dilindungi dan dibatasi sesuai role akun.
            </p>
        </div>
    </div>

</div>
@endsection
