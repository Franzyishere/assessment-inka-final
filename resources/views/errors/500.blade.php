<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon-inka-32.svg') }}?v={{ filemtime(public_path('favicon-inka-32.svg')) }}">
    <title>500 — Kendala Sistem | INKA Assessment Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center justify-center p-4 sm:p-6 lg:p-8 font-sans antialiased text-gray-800">
    <div class="w-full max-w-xl">
        <!-- Logo Header -->
        <div class="mb-8 flex justify-center">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo/logo-inka-full.svg') }}" alt="Logo PT INKA" class="h-10 sm:h-12 w-auto object-contain" onerror="this.onerror=null; this.src='{{ asset('favicon-inka-32.svg') }}';">
            </div>
        </div>

        <!-- Main Card -->
        <div class="overflow-hidden rounded-3xl border border-gray-200/90 bg-white p-6 sm:p-8 shadow-theme-lg">
            <!-- Badge & Icon -->
            <div class="flex flex-col items-center text-center">
                <div class="flex size-16 items-center justify-center rounded-2xl bg-error-50 text-error-600 ring-8 ring-error-100/60 shadow-xs mb-5">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                
                <span class="inline-flex items-center gap-1.5 rounded-full bg-error-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-error-700 border border-error-200/70">
                    <span class="size-1.5 rounded-full bg-error-500"></span>
                    Status 500 &bull; Kendala Sistem
                </span>

                <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                    Terjadi Kendala Teknis
                </h1>

                <p class="mt-3 text-sm leading-6 text-gray-600 max-w-md">
                    Sistem sedang mengalami kendala sementara saat memproses permintaan Anda. Tim teknis assessment telah mencatat kendala ini.
                </p>
            </div>

            <!-- Petunjuk Info Box -->
            <div class="mt-6 rounded-2xl border border-amber-100 bg-amber-50/60 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 mt-0.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </span>
                    <div class="text-xs leading-5 text-amber-900">
                        <p class="font-bold">Langkah yang Dapat Dilakukan:</p>
                        <p class="mt-1">Silakan coba beberapa saat lagi atau muat ulang halaman. Apabila Anda sedang berada di ruangan assessment, laporkan kendala ini kepada pengawas atau admin ruangan.</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-center">
                <button type="button" onclick="window.location.reload();" class="crud-btn-primary !h-11 px-6 text-sm font-semibold justify-center">
                    Coba Muat Ulang &#x21bb;
                </button>
                <a href="{{ auth()->check() ? (auth()->user()->hasRole('peserta_assessment') ? route('peserta-assessment.simulations.index') : route('dashboard')) : url('/') }}" class="crud-btn-secondary !h-11 px-5 text-sm font-semibold justify-center">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Footer Note -->
        <p class="mt-6 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} PT Industri Kereta Api (Persero). Seluruh hak cipta dilindungi.
        </p>
    </div>
</body>
</html>

