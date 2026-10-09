<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon-inka-32.svg') }}?v={{ filemtime(public_path('favicon-inka-32.svg')) }}">
    <title>419 — Sesi Kedaluwarsa | INKA Assessment Portal</title>
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
                <div class="flex size-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-8 ring-amber-100/60 shadow-xs mb-5">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-700 border border-amber-200/70">
                    <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Status 419 &bull; Sesi Kedaluwarsa
                </span>

                <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                    Sesi Pengerjaan Berakhir
                </h1>

                <p class="mt-3 text-sm leading-6 text-gray-600 max-w-md">
                    Halaman form atau sesi Anda telah kedaluwarsa karena tidak ada aktivitas dalam waktu tertentu demi keamanan akun.
                </p>
            </div>

            <!-- Petunjuk Info Box -->
            <div class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mt-0.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </span>
                    <div class="text-xs leading-5 text-emerald-900">
                        <p class="font-bold">Keamanan Data Anda Terjamin:</p>
                        <p class="mt-1">Jawaban simulasi yang sebelumnya telah tersimpan secara otomatis (autosave) tetap aman di database. Cukup muat ulang halaman untuk memperbarui token sesi Anda.</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-center">
                <button type="button" onclick="window.location.reload();" class="crud-btn-primary !h-11 px-6 text-sm font-semibold justify-center">
                    Muat Ulang Halaman &#x21bb;
                </button>
                <a href="{{ route('login') }}" class="crud-btn-secondary !h-11 px-5 text-sm font-semibold justify-center">
                    Masuk Kembali
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

