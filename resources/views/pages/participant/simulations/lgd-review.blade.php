@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Leaderless Group Discussion" />
<div x-data="lgdSession('{{ $session->expires_at?->toIso8601String() }}', '{{ route('peserta-assessment.simulations.events.store', $programSimulation) }}')" class="mx-auto max-w-6xl space-y-5">
    <div x-show="!secureMode" x-cloak class="fixed inset-0 z-99998 flex items-center justify-center bg-gray-900/80 p-4 backdrop-blur-sm"><div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-gray-900"><h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Aktifkan Mode Fullscreen</h2><p class="mt-2 text-sm text-gray-500">Simulasi 2 wajib dikerjakan dalam fullscreen. Timer tetap berjalan sejak simulasi dimulai.</p><p x-show="fullscreenError" x-text="fullscreenError" class="mt-3 text-sm text-error-500"></p><button type="button" @click="enableFullscreen" class="mt-6 w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Masuk Fullscreen & Lanjutkan</button></div></div>
    <div class="sticky top-20 z-30 flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-brand-500/30 dark:bg-gray-900"><div><span class="text-xs font-medium uppercase text-brand-500">Simulasi 2</span><h1 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">Leaderless Group Discussion</h1><p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Review materi dan jawaban Simulasi 1 sebagai bahan LGD. Konten hanya dapat dibaca.</p></div><div class="rounded-xl bg-white px-4 py-3 text-center shadow-sm dark:bg-white/10"><p class="text-[10px] uppercase text-gray-500">Sisa waktu</p><p class="mt-1 font-mono text-xl font-bold text-error-600" x-text="formattedTime"></p></div></div>
    @foreach($sourceSimulation->scenario->materialPages as $material)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Uraian Simulasi 1</h2></div>
            <iframe title="Uraian Simulasi 1" src="{{ route('peserta-assessment.simulations.material.pdf', [$sourceSimulation, $material]) }}#toolbar=1&navpanes=0" class="h-[65vh] min-h-[560px] w-full bg-gray-100"></iframe>
            <div class="border-t border-gray-200 p-5 dark:border-gray-800"><p class="text-xs font-medium uppercase text-gray-500">Jawaban Anda pada Simulasi 1</p><div class="mt-3 whitespace-pre-line rounded-xl bg-gray-50 p-5 text-sm leading-7 text-gray-700 dark:bg-white/5 dark:text-gray-300">{{ $responses[$material->page_order] ?? 'Tidak ada jawaban.' }}</div></div>
        </section>
    @endforeach
    <form method="POST" action="{{ route('peserta-assessment.simulations.lgd-review.submit', $programSimulation) }}" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]" onsubmit="return confirm('Simpan dan kumpulkan Simulasi 2 sekarang? Status selesai tidak dapat dibatalkan.')">@csrf<label class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300"><input type="checkbox" name="confirmation" value="1" required class="mt-0.5 size-4 rounded border-gray-300"><span>Saya menyatakan telah selesai mengikuti dan mereview bahan Simulasi 2.</span></label>@error('confirmation')<p class="mt-2 text-sm text-error-500">{{ $message }}</p>@enderror<div class="mt-5 flex justify-end"><button class="rounded-lg bg-success-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-success-600">Simpan & Kumpulkan</button></div></form>
</div>
@endsection

@push('scripts')
<script>
function lgdSession(expiresAt, endpoint) {
    return {
        expiresAt: new Date(expiresAt).getTime(), remaining: Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)), secureMode: false, fullscreenActivated: false, fullscreenError: '', sequence: 0, lastEvents: {},
        get formattedTime() { const h = Math.floor(this.remaining / 3600), m = Math.floor((this.remaining % 3600) / 60), s = this.remaining % 60; return [h,m,s].map(v => String(v).padStart(2,'0')).join(':') },
        init() { setInterval(() => { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)) }, 1000); document.addEventListener('fullscreenchange', () => { this.secureMode = !!document.fullscreenElement; if (this.fullscreenActivated && !document.fullscreenElement) this.report('fullscreen_exit') }) },
        async enableFullscreen() { try { await document.documentElement.requestFullscreen(); this.fullscreenActivated = true; this.secureMode = true; this.fullscreenError = '' } catch (_) { this.fullscreenError = 'Browser menolak fullscreen. Izinkan fullscreen lalu coba kembali.'; this.report('fullscreen_denied') } },
        report(type) { const now = Date.now(); if (now - (this.lastEvents[type] || 0) < 2000) return; this.lastEvents[type] = now; this.sequence++; fetch(endpoint, {method:'POST',keepalive:true,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({event_type:type,client_time:new Date().toISOString(),page_url:window.location.pathname,visibility_state:document.visibilityState,is_fullscreen:!!document.fullscreenElement,sequence:this.sequence})}).catch(() => {}) }
    }
}
</script>
@endpush
