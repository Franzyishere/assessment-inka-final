@extends('layouts.fullscreen-layout')

@section('content')
<main class="min-h-screen bg-gray-50 p-3 sm:p-5 lg:p-6">
<div x-data="lgdSession('{{ $session->expires_at?->toIso8601String() }}', '{{ route('peserta-assessment.simulations.events.store', $programSimulation) }}')" class="mx-auto min-w-0 max-w-7xl space-y-5">
    <div x-show="!secureMode" x-cloak class="fixed inset-0 z-99998 flex items-center justify-center bg-gray-900/90 p-4 backdrop-blur-md"><div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl"><h2 class="text-lg font-semibold text-gray-800" x-text="violations ? 'Halaman Pengerjaan Dikunci' : 'Aktifkan Mode Fullscreen'"></h2><p class="mt-2 text-sm leading-6 text-gray-500" x-text="securityMessage"></p><div x-show="violations > 0" class="mt-4 rounded-xl px-4 py-3 text-sm font-medium" :class="violations >= 3 ? 'bg-error-50 text-error-700' : 'bg-warning-50 text-warning-700'"><span x-text="violations"></span> aktivitas tercatat<span x-show="violations >= 3"> · perlu ditinjau asesor</span></div><p x-show="fullscreenError" x-text="fullscreenError" class="mt-3 text-sm text-error-500"></p><button type="button" @click="enableFullscreen" class="mt-6 w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Kembali ke Fullscreen & Lanjutkan</button></div></div>
    <div class="sticky top-3 z-30 flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-brand-500/30 dark:bg-gray-900"><div><span class="text-xs font-medium uppercase text-brand-500">Simulasi 2</span><h1 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">Leaderless Group Discussion</h1><p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Review materi dan jawaban Simulasi 1 sebagai bahan LGD. Konten hanya dapat dibaca.</p></div><div class="rounded-xl bg-white px-4 py-3 text-center shadow-sm dark:bg-white/10"><p class="text-[10px] uppercase text-gray-500">Sisa waktu</p><p class="mt-1 font-mono text-xl font-bold text-error-600" x-text="formattedTime"></p></div></div>
    @foreach($programSimulation->scenario->materialPages as $instruction)
        @if($instruction->attachment_path)
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="bg-gray-50/70 p-4 sm:p-5 lg:p-6 dark:bg-gray-900/30">
                    <div class="mx-auto max-w-5xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700">
                        <iframe title="Instruksi Leaderless Group Discussion" src="{{ route('peserta-assessment.simulations.material.pdf', [$programSimulation, $instruction]) }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" class="h-[52vh] min-h-[460px] max-h-[580px] w-full select-none bg-gray-100" referrerpolicy="same-origin"></iframe>
                    </div>
                </div>
            </section>
        @endif
    @endforeach
    @foreach($sourceSimulation->scenario->materialPages as $material)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Uraian Simulasi 1</h2><span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300"><svg class="size-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6.5 9V6.75a3.5 3.5 0 0 1 7 0V9M5.75 9h8.5v7h-8.5V9Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>Hanya baca</span></div>
            <div class="bg-gray-50/70 p-4 sm:p-5 lg:p-6 dark:bg-gray-900/30">
                <div class="mx-auto max-w-5xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700">
                    <iframe title="Uraian Simulasi 1 — hanya baca" src="{{ route('peserta-assessment.simulations.material.pdf', [$sourceSimulation, $material]) }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" class="h-[52vh] min-h-[460px] max-h-[580px] w-full select-none bg-gray-100" referrerpolicy="same-origin"></iframe>
                </div>
            </div>
            <div class="border-t border-gray-200 p-5 dark:border-gray-800"><p class="text-xs font-medium uppercase text-gray-500">Jawaban Anda pada Simulasi 1</p><div class="tiptap-answer-content mt-3 rounded-xl bg-gray-50 p-5 text-sm leading-7 text-gray-700 dark:bg-white/5 dark:text-gray-300">@php($savedResponse = $responses[$material->page_order] ?? '')@if(str_contains($savedResponse, '<')){!! $savedResponse !!}@elseif($savedResponse){!! nl2br(e($savedResponse)) !!}@else Tidak ada jawaban. @endif</div></div>
        </section>
    @endforeach
    <form x-ref="lgdForm" method="POST" action="{{ route('peserta-assessment.simulations.lgd-review.submit', $programSimulation) }}" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">@csrf<label class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300"><input type="checkbox" name="confirmation" value="1" required class="mt-0.5 size-4 rounded border-gray-300"><span>Saya menyatakan telah selesai mengikuti dan mereview bahan Simulasi 2.</span></label>@error('confirmation')<p class="mt-2 text-sm text-error-500">{{ $message }}</p>@enderror<div class="mt-5 flex justify-end"><button type="button" class="rounded-lg bg-success-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-success-600" @click="$dispatch('confirm-dialog', { title: 'Selesaikan Simulasi 2?', message: 'Status selesai tidak dapat dibatalkan setelah konfirmasi diselesaikan.', confirmLabel: 'Ya, Selesaikan', onConfirm: () => $refs.lgdForm.requestSubmit() })">Simulasi Sudah Selesai</button></div></form>
</div>
</main>
@endsection

@push('scripts')
<script>
function lgdSession(expiresAt, endpoint) {
    return {
        expiresAt: new Date(expiresAt).getTime(), remaining: Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)), violations: 0, secureMode: false, fullscreenActivated: false, fullscreenError: '', lockReason: '', sequence: 0, lastEvents: {},
        get formattedTime() { const h = Math.floor(this.remaining / 3600), m = Math.floor((this.remaining % 3600) / 60), s = this.remaining % 60; return [h,m,s].map(v => String(v).padStart(2,'0')).join(':') },
        get securityMessage() { if (!this.fullscreenActivated) return 'Simulasi wajib dijalankan dalam mode fullscreen.'; return this.lockReason === 'tab_hidden' ? 'Perpindahan tab terdeteksi. Timer tetap berjalan sampai Anda kembali melanjutkan.' : 'Mode fullscreen terhenti. Timer tetap berjalan sampai fullscreen diaktifkan kembali.' },
        init() { setInterval(() => { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)) }, 1000); document.addEventListener('fullscreenchange', () => { if (this.fullscreenActivated && !document.fullscreenElement) { this.secureMode = false; setTimeout(() => { if (!document.hidden) { this.lockReason = 'fullscreen_exit'; this.report('fullscreen_exit') } }, 250) } }); document.addEventListener('visibilitychange', () => { if (document.hidden && this.fullscreenActivated) { this.secureMode = false; this.lockReason = 'tab_hidden'; this.report('tab_hidden') } }) },
        async enableFullscreen() { try { if (!document.fullscreenElement) await document.documentElement.requestFullscreen(); this.fullscreenActivated = true; this.secureMode = true; this.lockReason = ''; this.fullscreenError = '' } catch (_) { this.fullscreenError = 'Browser menolak fullscreen. Izinkan fullscreen lalu coba kembali.'; this.report('fullscreen_denied') } },
        report(type) { const now = Date.now(); if (now - (this.lastEvents[type] || 0) < 2000) return; this.lastEvents[type] = now; this.violations++; this.sequence++; fetch(endpoint, {method:'POST',keepalive:true,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({event_type:type,client_time:new Date().toISOString(),page_url:window.location.pathname,visibility_state:document.visibilityState,is_fullscreen:!!document.fullscreenElement,sequence:this.sequence})}).then(response => response.ok ? response.json() : null).then(data => { if (data?.violation_count !== undefined) this.violations = data.violation_count }).catch(() => {}) }
    }
}
</script>
@endpush
