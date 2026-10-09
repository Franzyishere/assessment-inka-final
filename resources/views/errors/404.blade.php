<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon-inka-32.svg') }}?v={{ filemtime(public_path('favicon-inka-32.svg')) }}">
    <title>404 — Halaman Tidak Ditemukan | INKA Assessment Portal</title>
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
                <div class="flex size-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-8 ring-blue-100/60 shadow-xs mb-5">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </div>
                
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-blue-700 border border-blue-200/70">
                    <span class="size-1.5 rounded-full bg-blue-500"></span>
                    Status 404 &bull; Halaman Tidak Ditemukan
                </span>

                <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                    Halaman Tidak Ditemukan
                </h1>

                <p class="mt-3 text-sm leading-6 text-gray-600 max-w-md">
                    {{ $exception->getMessage() ?: 'Halaman atau data simulasi yang Anda tuju tidak ditemukan, sudah dipindahkan, atau alamat URL yang dimasukkan salah.' }}
                </p>
            </div>

            <!-- Petunjuk Info Box -->
            <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-600 mt-0.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                    </span>
                    <div class="text-xs leading-5 text-gray-700">
                        <p class="font-bold text-gray-900">Petunjuk Navigasi:</p>
                        <p class="mt-1">Periksa kembali penulisan URL di browser Anda atau gunakan tombol di bawah untuk kembali ke halaman utama portal assessment.</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-center">
                <button type="button" onclick="if(window.history.length > 1){ window.history.back(); } else { window.location.href='{{ auth()->check() && auth()->user()->hasRole('peserta_assessment') ? route('peserta-assessment.simulations.index') : url('/') }}'; }" class="crud-btn-secondary !h-11 px-5 text-sm font-semibold justify-center">
                    &larr; Kembali ke Halaman Sebelumnya
                </button>
                <a href="{{ auth()->check() ? (auth()->user()->hasRole('peserta_assessment') ? route('peserta-assessment.simulations.index') : route('dashboard')) : url('/') }}" class="crud-btn-primary !h-11 px-6 text-sm font-semibold justify-center">
                    Dashboard Utama &rarr;
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

