@extends('layouts.fullscreen-layout')

@section('content')
<main class="min-h-screen bg-gray-50 p-3 sm:p-5 lg:p-6">
<div x-data="caseResponseSession('{{ $session->expires_at?->toIso8601String() }}', '{{ route('peserta-assessment.simulations.events.store', $programSimulation) }}')" class="mx-auto min-w-0 max-w-7xl space-y-5">
    <div x-show="!secureMode" x-cloak class="fixed inset-0 z-99998 flex items-center justify-center bg-gray-900/90 p-4 backdrop-blur-md"><div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl"><h2 class="text-lg font-semibold text-gray-800" x-text="violations ? 'Halaman Pengerjaan Dikunci' : 'Aktifkan Mode Fullscreen'"></h2><p class="mt-2 text-sm leading-6 text-gray-500" x-text="securityMessage"></p><div x-show="violations > 0" class="mt-4 rounded-xl px-4 py-3 text-sm font-medium" :class="violations >= 3 ? 'bg-error-50 text-error-700' : 'bg-warning-50 text-warning-700'"><span x-text="violations"></span> aktivitas tercatat<span x-show="violations >= 3"> · perlu ditinjau asesor</span></div><p x-show="fullscreenError" x-text="fullscreenError" class="mt-3 text-sm text-error-500"></p><button type="button" @click="enableFullscreen" class="mt-6 w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Kembali ke Fullscreen & Lanjutkan</button></div></div>
    <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-white/[0.03]">
        <div><p class="text-xs font-medium uppercase text-brand-500">Simulasi {{ $programSimulation->scenario->type->sequence }}</p><h1 class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $programSimulation->scenario->type->name }}</h1></div>
        <div class="flex items-center gap-2"><button type="button" @click="enableFullscreen" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300">Aktifkan fullscreen</button><span x-show="violations > 0" x-text="violations + ' pelanggaran fullscreen'" class="rounded-lg bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700"></span><span x-text="String(Math.floor(remaining / 60)).padStart(2, '0') + ':' + String(remaining % 60).padStart(2, '0')" class="rounded-lg bg-error-50 px-4 py-2 text-sm font-semibold text-error-600"></span></div>
    </div>
    <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"><h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Materi Kasus</h2><div class="mt-4 whitespace-pre-line text-sm leading-7 text-gray-600 dark:text-gray-300">{{ $programSimulation->scenario->description }}</div></section>
    <form x-ref="caseForm" method="POST" action="{{ route('peserta-assessment.simulations.case-response.submit', $programSimulation) }}" class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">@csrf<label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Respons Anda *</label><textarea name="response" rows="16" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">{{ old('response') }}</textarea>@error('response')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror<div class="mt-5 flex justify-end"><button type="button" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600" @click="$dispatch('confirm-dialog', { title: 'Kumpulkan respons?', message: 'Jawaban tidak dapat diubah setelah dikumpulkan.', confirmLabel: 'Ya, Kumpulkan', onConfirm: () => $refs.caseForm.requestSubmit() })">Kumpulkan Respons</button></div></form>
</div>
</main>
@endsection

@push('scripts')
<script>
function caseResponseSession(expiresAt, endpoint) {
    return {
        expiresAt: new Date(expiresAt).getTime(), remaining: Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)), violations: 0, fullscreenActivated: false, secureMode: false, fullscreenError: '', lockReason: '', sequence: 0, lastEvents: {},
        get securityMessage() { if (!this.fullscreenActivated) return 'Assessment wajib dikerjakan dalam mode fullscreen.'; return this.lockReason === 'tab_hidden' ? 'Perpindahan tab terdeteksi. Timer tetap berjalan sampai Anda kembali melanjutkan.' : 'Mode fullscreen terhenti. Timer tetap berjalan sampai fullscreen diaktifkan kembali.' },
        init() {
            setInterval(() => { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)) }, 1000);
            document.addEventListener('fullscreenchange', () => { if (this.fullscreenActivated && !document.fullscreenElement) { this.secureMode = false; setTimeout(() => { if (!document.hidden) { this.lockReason = 'fullscreen_exit'; this.report('fullscreen_exit') } }, 250) } });
            document.addEventListener('visibilitychange', () => { if (document.hidden && this.fullscreenActivated) { this.secureMode = false; this.lockReason = 'tab_hidden'; this.report('tab_hidden') } });
        },
        async enableFullscreen() {
            try { if (!document.fullscreenElement) await document.documentElement.requestFullscreen(); this.fullscreenActivated = true; this.secureMode = true; this.lockReason = ''; this.fullscreenError = '' }
            catch (_) { this.fullscreenError = 'Browser menolak fullscreen. Izinkan fullscreen lalu coba kembali.'; this.report('fullscreen_denied') }
        },
        report(type) {
            const now = Date.now(); if (now - (this.lastEvents[type] || 0) < 2000) return; this.lastEvents[type] = now; this.violations++; this.sequence++;
            fetch(endpoint, { method:'POST', keepalive:true, headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify({event_type:type,client_time:new Date().toISOString(),page_url:window.location.pathname,visibility_state:document.visibilityState,is_fullscreen:!!document.fullscreenElement,sequence:this.sequence}) }).then(response => response.ok ? response.json() : null).then(data => { if (data?.violation_count !== undefined) this.violations = data.violation_count }).catch(() => {})
        }
    }
}
</script>
@endpush
