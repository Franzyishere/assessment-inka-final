@extends('layouts.app')

@section('content')
<div x-data="antiCheatSession('{{ $session->expires_at?->toIso8601String() }}', '{{ route('peserta-assessment.simulations.events.store', $programSimulation) }}')" class="mx-auto max-w-5xl space-y-5">
    <div x-show="!secureMode" x-cloak class="fixed inset-0 z-99998 flex items-center justify-center bg-gray-900/80 p-4 backdrop-blur-sm"><div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-gray-900"><h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Aktifkan Mode Fullscreen</h2><p class="mt-2 text-sm leading-6 text-gray-500">Assessment wajib dikerjakan dalam mode fullscreen. Jika keluar dari fullscreen, halaman pengerjaan akan dikunci sampai fullscreen diaktifkan kembali.</p><p x-show="fullscreenError" x-text="fullscreenError" class="mt-3 text-sm text-error-500"></p><button type="button" @click="enableFullscreen" class="mt-6 w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Masuk Fullscreen & Lanjutkan</button></div></div>
    <div class="sticky top-20 z-30 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="min-w-0">
                <div class="flex items-center gap-2"><p class="text-xs font-medium uppercase text-brand-500">Halaman {{ $pageNumber }} dari {{ $pageCount }}</p><span class="h-1 w-1 rounded-full bg-gray-300"></span><p class="text-xs text-gray-500">{{ $programSimulation->scenario->duration_minutes }} menit</p></div>
                <h1 class="mt-1 truncate font-semibold text-gray-800 dark:text-white/90">{{ $programSimulation->scenario->type->name }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                <button type="button" @click="enableFullscreen" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7 3H3v4M13 3h4v4M7 17H3v-4M13 17h4v-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span x-text="fullscreenActivated && document.fullscreenElement ? 'Fullscreen aktif' : 'Aktifkan fullscreen'"></span>
                </button>
                <div x-show="violations > 0" x-cloak class="rounded-lg bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400"><span x-text="violations"></span> pelanggaran fullscreen</div>
                <div class="flex min-w-36 items-center gap-3 rounded-xl border px-3 py-2 transition-colors" :class="timerTone" role="timer" aria-live="polite">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/70 dark:bg-white/10"><svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="11" r="6.5" stroke="currentColor" stroke-width="1.5"/><path d="M10 7.5V11l2.25 1.5M7.5 2.75h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
                    <div><p class="text-[10px] font-medium uppercase tracking-wide opacity-70" x-text="timerLabel"></p><p class="font-mono text-lg font-bold leading-5 tabular-nums" x-text="formattedTime"></p></div>
                </div>
            </div>
        </div>
        <div class="h-1 bg-gray-100 dark:bg-gray-800"><div class="h-full transition-all duration-1000" :class="remaining <= 300 ? 'bg-error-500' : (remaining <= 900 ? 'bg-warning-500' : 'bg-brand-500')" :style="`width: ${Math.max(0, Math.min(100, (remaining / initialRemaining) * 100))}%`"></div></div>
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800"><div><span class="text-xs font-medium text-brand-500">Materi {{ $pageNumber }}</span><h2 class="mt-1 font-semibold text-gray-800 dark:text-white/90">Uraian Simulasi</h2></div><a target="_blank" href="{{ route('peserta-assessment.simulations.material.pdf', [$programSimulation, $material]) }}" class="text-sm font-medium text-brand-500">Buka PDF di tab baru</a></div>
        <iframe title="Materi PDF {{ $pageNumber }}" src="{{ route('peserta-assessment.simulations.material.pdf', [$programSimulation, $material]) }}#toolbar=1&navpanes=0" class="h-[72vh] min-h-[640px] w-full bg-gray-100"></iframe>
        @if($material->content)<div class="border-t border-gray-200 px-5 py-4 text-sm text-gray-500 dark:border-gray-800">{{ $material->content }}</div>@endif
    </div>
    <form method="POST" action="{{ route('peserta-assessment.simulations.material.save', [$programSimulation, $pageNumber]) }}" class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]" @if($pageCount === 1) onsubmit="return confirm('Simpan dan kumpulkan jawaban sekarang? Setelah dikumpulkan, jawaban tidak dapat diubah.')" @endif>
        @csrf @method('PUT')
        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Jawaban Materi {{ $pageNumber }} @if($material->is_required)<span class="text-error-500">*</span>@endif</label>
        <textarea name="response" rows="10" @required($material->is_required) class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90">{{ old('response', $response) }}</textarea>
        @error('response')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
        <div class="mt-5 flex items-center justify-between">
            <a href="{{ route('peserta-assessment.simulations.material', [$programSimulation, max(1, $pageNumber - 1)]) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Sebelumnya</a>
            <button @if($pageCount === 1) name="submit_after_save" value="1" @endif class="rounded-lg px-5 py-2.5 text-sm font-medium text-white {{ $pageCount === 1 ? 'bg-success-500 hover:bg-success-600' : 'bg-brand-500 hover:bg-brand-600' }}">{{ $pageCount === 1 ? 'Simpan & Kumpulkan' : ($pageNumber < $pageCount ? 'Simpan & Berikutnya' : 'Simpan Jawaban') }}</button>
        </div>
    </form>
    @if ($pageNumber === $pageCount && $pageCount > 1)
        <form method="POST" action="{{ route('peserta-assessment.simulations.submit', $programSimulation) }}" class="flex justify-end" onsubmit="return confirm('Setelah dikumpulkan, jawaban tidak dapat diubah. Lanjutkan?')">@csrf<button class="rounded-lg bg-success-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-success-600">Kumpulkan Simulasi</button></form>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function antiCheatSession(expiresAt, endpoint) {
        return {
            expiresAt: new Date(expiresAt).getTime(),
            initialRemaining: Math.max(1, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)),
            remaining: Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)),
            violations: 0,
            fullscreenActivated: false,
            secureMode: false,
            fullscreenError: '',
            sequence: 0,
            lastEvents: {},
            get formattedTime() {
                const hours = Math.floor(this.remaining / 3600);
                const minutes = Math.floor((this.remaining % 3600) / 60);
                const seconds = this.remaining % 60;
                return [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':');
            },
            get timerTone() {
                if (this.remaining <= 300) return 'border-error-200 bg-error-50 text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400';
                if (this.remaining <= 900) return 'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400';
                return 'border-brand-200 bg-brand-50 text-brand-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-400';
            },
            get timerLabel() {
                if (this.remaining === 0) return 'Waktu habis';
                if (this.remaining <= 300) return 'Segera berakhir';
                return 'Sisa waktu';
            },
            init() {
                setInterval(() => { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)) }, 1000);
                document.addEventListener('fullscreenchange', () => {
                    this.secureMode = !!document.fullscreenElement;
                    if (this.fullscreenActivated && !document.fullscreenElement) this.report('fullscreen_exit');
                });
            },
            async enableFullscreen() {
                try { await document.documentElement.requestFullscreen(); this.fullscreenActivated = true; this.secureMode = true; this.fullscreenError = ''; }
                catch (_) { this.fullscreenError = 'Browser menolak fullscreen. Izinkan fullscreen pada pengaturan situs lalu coba kembali.'; this.report('fullscreen_denied'); }
            },
            report(eventType) {
                const now = Date.now();
                if (now - (this.lastEvents[eventType] || 0) < 2000) return;
                this.lastEvents[eventType] = now;
                this.violations++;
                this.sequence++;
                fetch(endpoint, {
                    method: 'POST',
                    keepalive: true,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ event_type: eventType, client_time: new Date().toISOString(), page_url: window.location.pathname, visibility_state: document.visibilityState, is_fullscreen: !!document.fullscreenElement, sequence: this.sequence })
                }).then(response => response.ok ? response.json() : null).then(data => { if (data?.violation_count !== undefined) this.violations = data.violation_count }).catch(() => {});
            }
        }
    }
</script>
@endpush
