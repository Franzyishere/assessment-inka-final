@extends('layouts.fullscreen-layout', ['title' => 'Undangan Assessment'])
@push('meta')
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
@endpush
@section('content')
<main class="flex min-h-screen items-center justify-center bg-gray-50 p-5">
    <section class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold text-gray-900">INKA Assessment Portal</h1>
        <p class="mt-2 font-semibold text-brand-700">{{ $invitation->participant->program->name }}</p>
        <p class="mt-2 text-sm text-gray-600">Akses pada {{ $invitation->valid_from->copy()->timezone(config('assessment_access.timezone'))->format('d M Y') }}, pukul 00.00–23.59 WIB.</p>
        @if(session('success'))<p role="status" class="mt-4 rounded-lg bg-success-50 p-3 text-sm text-success-700">{{ session('success') }}</p>@endif
        @if($errors->any())<div role="alert" class="mt-4 rounded-lg bg-error-50 p-3 text-sm text-error-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if($invitation->isAccessible())
            <form method="POST" action="{{ route('assessment.invitation.otp', $token) }}" class="mt-6 space-y-3" x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <label for="email" class="block text-sm font-semibold text-gray-800">Email penerima undangan</label>
                <input id="email" name="email" type="email" required maxlength="255" autocomplete="email" value="{{ old('email') }}" class="h-12 w-full rounded-lg border border-gray-300 px-3 text-gray-900">
                <button type="submit" :disabled="sending" class="crud-btn-primary w-full" x-text="sending ? 'Mengirim…' : 'Kirim OTP'">Kirim OTP</button>
            </form>
            @if(session('assessment_otp_requested') === hash('sha256', $token))
                <form method="POST" action="{{ route('assessment.invitation.verify', $token) }}" class="mt-6 space-y-3" x-data="{ sending: false }" @submit="sending = true">
                    @csrf
                    <label for="otp" class="block text-sm font-semibold text-gray-800">Kode OTP</label>
                    <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" class="h-12 w-full rounded-lg border border-gray-300 px-3 text-center text-xl tracking-widest text-gray-900">
                    <p class="text-xs text-gray-600">Berlaku 10 menit, maksimal 5 percobaan. Meminta kode baru akan membatalkan kode lama. Gunakan browser ini untuk verifikasi.</p>
                    <button type="submit" :disabled="sending" class="crud-btn-primary w-full">Verifikasi dan Masuk</button>
                </form>
            @endif
        @else
            <p role="status" class="mt-6 rounded-lg bg-gray-100 p-4 text-sm text-gray-800">{{ $invitation->accessLabel() }}. Undangan hanya berlaku pada hari pelaksanaan. Hubungi admin jika jadwal atau undangan perlu diperbarui.</p>
        @endif
        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm text-gray-600 underline">Login admin / asesor</a>
    </section>
</main>
@endsection
