@extends('layouts.fullscreen-layout', ['title' => 'Verifikasi Undangan Assessment'])
@push('meta')
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
@endpush
@section('content')
<main class="flex min-h-screen items-center justify-center bg-gray-50 p-5">
    <section class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900">INKA Assessment Portal</h1>
            <p class="mt-2 font-semibold text-brand-700">{{ $invitation->participant->program->name }}</p>
            @php
                $closeTime = ($invitation->expires_at->format('H:i:s') === '00:00:00') ? '17.00' : $invitation->expires_at->copy()->timezone(config('assessment_access.timezone'))->format('H.i');
            @endphp
            <p class="mt-1 text-xs text-gray-500">Akses pada {{ $invitation->valid_from->copy()->timezone(config('assessment_access.timezone'))->format('d M Y') }}, sampai pukul {{ $closeTime }} WIB</p>
        </div>

        @if(session('success'))
            <p role="status" class="mt-4 rounded-lg bg-success-50 p-3 text-sm text-success-700">{{ session('success') }}</p>
        @elseif(session('error'))
            <p role="alert" class="mt-4 rounded-lg bg-error-50 p-3 text-sm text-error-700">{{ session('error') }}</p>
        @elseif($invitation->isAccessible())
            <div class="mt-4 rounded-lg bg-brand-50 border border-brand-100 p-3.5 text-xs leading-relaxed text-brand-800">
                <span class="font-semibold">Kode OTP telah dikirimkan ke email Anda:</span><br>
                <span class="font-mono text-sm font-bold text-brand-900">{{ $maskedEmail }}</span><br>
                Silakan periksa kotak masuk (inbox) atau folder spam Anda.
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="mt-4 rounded-lg bg-error-50 p-3 text-sm text-error-700">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if($invitation->isAccessible())
            <form method="POST" action="{{ route('assessment.invitation.verify', $token) }}" class="mt-6 space-y-4" x-data="{ verifying: false }" @submit="verifying = true">
                @csrf
                <div>
                    <label for="otp" class="block text-sm font-semibold text-gray-800 text-center">Masukkan 8 Karakter Kode OTP</label>
                    <input id="otp" name="otp" type="text" inputmode="text" pattern="[A-Za-z0-9]{8}" maxlength="8" required autofocus autocomplete="one-time-code" placeholder="••••••••" class="mt-2 h-14 w-full rounded-xl border border-gray-300 px-3 text-center font-mono text-xl sm:text-2xl tracking-[0.25em] sm:tracking-[0.35em] uppercase text-gray-900 placeholder:tracking-widest focus:border-brand-500 focus:ring-brand-500 shadow-xs" x-on:input="$el.value = $el.value.toUpperCase()">
                    <p class="mt-2 text-center text-xs text-gray-500">Kombinasi 8 huruf dan angka. Berlaku 10 menit, maksimal 5 percobaan per kode.</p>
                </div>
                <button type="submit" :disabled="verifying" class="crud-btn-primary w-full h-11 text-base font-semibold" x-text="verifying ? 'Memverifikasi…' : 'Verifikasi dan Masuk'">Verifikasi dan Masuk</button>
            </form>

            <div class="mt-6 pt-5 border-t border-gray-100 text-center text-xs text-gray-500" x-data="{ cooldown: {{ session('success') ? 60 : 0 }}, timer: null }" x-init="if (cooldown > 0) { timer = setInterval(() => { cooldown--; if (cooldown <= 0) clearInterval(timer); }, 1000); }">
                <p>Belum menerima kode OTP atau kode kedaluwarsa?</p>
                <form method="POST" action="{{ route('assessment.invitation.otp', $token) }}" class="mt-2 inline-block">
                    @csrf
                    <button type="submit" :disabled="cooldown > 0" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:text-brand-800 underline disabled:opacity-50 disabled:no-underline disabled:cursor-not-allowed">
                        <span x-show="cooldown <= 0">Kirim Ulang Kode OTP</span>
                        <span x-show="cooldown > 0" x-text="`Kirim ulang kode (${cooldown}s)`"></span>
                    </button>
                </form>
            </div>
        @else
            <p role="status" class="mt-6 rounded-lg bg-gray-100 p-4 text-sm text-gray-800 text-center">{{ $invitation->accessLabel() }}. Undangan hanya berlaku pada hari pelaksanaan. Hubungi admin jika jadwal atau undangan perlu diperbarui.</p>
        @endif

        <!-- <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-xs text-gray-500 hover:text-gray-700 underline">Login akun admin / asesor</a>
        </div> -->
    </section>
</main>
@endsection
