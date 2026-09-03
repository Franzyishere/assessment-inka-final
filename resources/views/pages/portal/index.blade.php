@extends('layouts.portal')

@push('styles')
<style>
    .portal-background-wave {
        width: 200%;
        animation: portal-background-drift 11s linear infinite;
        will-change: transform;
    }

    @keyframes portal-background-drift {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .portal-background-wave { animation: none; }
    }
</style>
@endpush

@section('content')
<div class="relative min-h-screen overflow-hidden bg-[#f7f7f8]">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[350px] bg-gradient-to-b from-brand-950 via-brand-800 to-brand-600 sm:h-[360px]"></div>
    <div class="pointer-events-none absolute -right-24 top-12 size-80 rounded-full border border-white/10"></div>
    <div class="pointer-events-none absolute right-20 top-52 size-32 rounded-full bg-white/5 blur-xl"></div>
    <div class="pointer-events-none absolute inset-x-0 top-[349px] z-[1] h-28 overflow-hidden sm:top-[359px] sm:h-32" aria-hidden="true">
        <svg class="portal-background-wave absolute left-0 top-0 h-full max-w-none" viewBox="0 0 2880 140" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 0H2880V64C2640 18 2400 18 2160 64C1920 110 1680 110 1440 64C1200 18 960 18 720 64C480 110 240 110 0 64V0Z" fill="#c91422"/>
        </svg>
    </div>

    <header class="relative z-10 border-b border-white/10">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ route('portal.index') }}" class="flex items-center gap-3">
                <span class="flex h-14 w-44 items-center overflow-hidden rounded-xl px-3 shadow-theme-sm sm:w-52">
                    <img src="{{ asset('images/logo/logo-inka-putih.png') }}" alt="INKA" class="h-auto w-full object-contain">
                </span>
            </a>
            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block"><p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p><p class="text-xs text-white/60">{{ auth()->user()->roleLabel() }}</p></div>
                <span class="flex size-10 items-center justify-center rounded-full bg-white/15 text-sm font-bold text-white ring-1 ring-white/20">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="rounded-lg border border-white/20 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/10">Keluar</button></form>
            </div>
        </div>
    </header>

    <main class="relative z-10 mx-auto max-w-7xl px-5 pb-12 pt-14 sm:px-8 sm:pt-20">
        <div class="max-w-3xl">
            <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/80">Human Capital & General Affairs Digital Platform</span>
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-5xl">INKA Assessment System</h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-white/70 sm:text-base">Portal pengelolaan dan pelaksanaan Assessment PT Industri Kereta Api (Persero).</p>
        </div>

        <section class="mt-12 max-w-2xl">
            <article class="group flex min-h-[360px] flex-col overflow-hidden rounded-3xl border border-white/70 bg-white p-6 shadow-theme-lg transition duration-300 hover:-translate-y-1 hover:shadow-2xl sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 4h8m-8 4h8M7 2h10a2 2 0 0 1 2 2v17l-7-3-7 3V4a2 2 0 0 1 2-2Z"/></svg>
                    </span>
                    @if($assessmentAvailable)<span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-700"><span class="size-1.5 rounded-full bg-success-500"></span>Aktif</span>@else<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500">Tidak Ada Akses</span>@endif
                </div>
                <div class="mt-7"><h2 class="text-2xl font-bold text-gray-900">Assessment</h2><p class="mt-3 text-sm leading-6 text-gray-500">{{ $assessmentDescription }}</p></div>
                <div class="mt-auto pt-8">
                    @if($assessmentAvailable)
                        <a href="{{ $assessmentUrl }}" class="flex w-full items-center justify-between rounded-xl bg-brand-600 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700"><span>Buka Assessment</span><span aria-hidden="true">→</span></a>
                    @else
                        <span class="flex w-full cursor-not-allowed items-center justify-center rounded-xl bg-gray-100 px-5 py-3.5 text-sm font-semibold text-gray-400">Akses Tidak Tersedia</span>
                    @endif
                </div>
            </article>
        </section>

        <footer class="mt-10 flex flex-col gap-2 border-t border-gray-200 pt-6 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between"><p>© {{ date('Y') }} PT Industri Kereta Api (Persero)</p><p>INKA Assessment System</p></footer>
    </main>
</div>
@endsection
