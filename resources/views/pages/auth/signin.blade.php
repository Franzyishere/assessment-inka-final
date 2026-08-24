@extends('layouts.fullscreen-layout')

@section('content')
<div class="relative min-h-screen bg-white dark:bg-gray-900">
    <div class="relative flex min-h-screen flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-8">
                <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">Masuk</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Masukkan email dan password untuk mengakses Assessment INKA.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                    {{ $errors->first() }}
                </div>
            @endif

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

    <div class="bg-brand-950 absolute inset-y-0 right-0 hidden w-1/2 items-center justify-center lg:flex dark:bg-white/5">
        <x-common.common-grid-shape />
        <div class="z-1 flex max-w-sm flex-col items-center px-8 text-center">
            <div class="mb-6 w-full rounded-2xl bg-white p-5 shadow-theme-lg">
                <img src="{{ asset('images/logo/imagesinka-animated.svg') }}" alt="Assessment INKA" class="h-auto w-full max-w-[300px]" />
            </div>
            <p class="text-gray-400 dark:text-white/60">Portal assessment internal dan rekrutmen PT INKA.</p>
        </div>
    </div>

    <div class="fixed right-6 bottom-6 z-50">
        <button type="button" class="bg-brand-500 hover:bg-brand-600 inline-flex size-14 items-center justify-center rounded-full text-white transition-colors" @click.prevent="$store.theme.toggle()">
            <span class="text-lg" x-text="$store.theme.theme === 'dark' ? '☀' : '☾'"></span>
        </button>
    </div>
</div>
@endsection
