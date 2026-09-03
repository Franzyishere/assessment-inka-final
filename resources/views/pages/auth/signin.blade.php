@extends('layouts.fullscreen-layout')

@push('styles')
<style>
    .inka-login-background {
        background-image: url('{{ asset('images/backgrounds/gedung-inka-login.jpg') }}');
        background-position: center;
        background-size: cover;
    }
</style>
@endpush

@section('content')
<main class="inka-login-background relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-10">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-black/5 via-transparent to-black/30"></div>

    <section class="relative z-10 grid w-full max-w-5xl overflow-hidden rounded-3xl border border-white/30 bg-white/5 shadow-[0_24px_80px_rgba(0,0,0,.3)] backdrop-blur-none lg:min-h-[570px] lg:grid-cols-[1fr_.9fr]">
        <div class="relative hidden flex-col justify-center overflow-hidden border-r border-white/15 p-10 text-white lg:flex xl:p-12">
            <!-- <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-950/20 to-transparent"></div> -->
            <div class="relative">
                <div class="flex h-20 w-64 items-center overflow-hidden rounded-2xl bg-white/100 px-5 shadow-xl">
                    <video autoplay muted loop playsinline preload="metadata" disablepictureinpicture poster="{{ asset('images/logo/logo-inka-full.svg') }}" class="h-full w-full object-contain" aria-label="Logo animasi PT INKA">
                        <source src="{{ asset('images/logo/logo-inka-motion.mp4') }}?v={{ filemtime(public_path('images/logo/logo-inka-motion.mp4')) }}" type="video/mp4">
                        <img src="{{ asset('images/logo/logo-inka-full.svg') }}" alt="Logo PT INKA" class="h-auto w-full object-contain">
                    </video>
                </div>
                <div class="mt-10 max-w-lg">
                    <h1 class="mt-5 text-4xl font-bold leading-tight">Assessment System INKA</h1>
                    <p class="mt-5 max-w-md text-sm leading-7 text-white/75 xl:text-base">Akses layanan Assessment PT Industri Kereta Api (Persero) secara aman sesuai peran dan penugasan Anda.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center bg-white/75 px-6 py-9 backdrop-blur-lg sm:px-10 lg:px-12">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-7 flex justify-center lg:hidden">
                    <div class="flex h-20 w-60 items-center overflow-hidden rounded-2xl bg-white px-4 shadow-lg">
                        <video autoplay muted loop playsinline preload="metadata" disablepictureinpicture poster="{{ asset('images/logo/logo-inka-full.svg') }}" class="h-full w-full object-contain" aria-label="Logo animasi PT INKA">
                            <source src="{{ asset('images/logo/logo-inka-motion.mp4') }}?v={{ filemtime(public_path('images/logo/logo-inka-motion.mp4')) }}" type="video/mp4">
                            <img src="{{ asset('images/logo/logo-inka-full.svg') }}" alt="Logo PT INKA" class="h-auto w-full object-contain">
                        </video>
                    </div>
                </div>

                <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Selamat datang kembali</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900">Masuk ke akun Anda</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">Gunakan akun yang telah terdaftar untuk melanjutkan.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Email <span class="text-error-500">*</span></label>
                            <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg></span><input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@inka.co.id" class="h-12 w-full rounded-xl border border-gray-200 bg-white/80 py-3 pl-12 pr-4 text-sm text-gray-800 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"></div>
                            @error('email')<p class="mt-1.5 text-sm text-error-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-gray-700">Password <span class="text-error-500">*</span></label>
                            <div x-data="{ showPassword: false }" class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span><input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" class="h-12 w-full rounded-xl border border-gray-200 bg-white/80 py-3 pl-12 pr-16 text-sm text-gray-800 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"><button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-brand-700" x-text="showPassword ? 'Tutup' : 'Lihat'"></button></div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-600"><input type="checkbox" name="remember" value="1" class="size-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">Ingat saya</label>
                        <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:-translate-y-0.5 hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/20">Masuk <span aria-hidden="true">→</span></button>
                    </div>
                </form>

                <p class="mt-7 flex items-center justify-center gap-2 text-xs text-gray-400"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 10V8a5 5 0 0 1 10 0v2m-11 0h12v10H6V10Z"/></svg>Akses terenkripsi dan dibatasi berdasarkan role.</p>
            </div>
        </div>
    </section>
</main>
@endsection
