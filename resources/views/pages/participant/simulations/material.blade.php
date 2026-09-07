@extends('layouts.fullscreen-layout')

@section('content')
<main class="min-h-screen bg-gray-50 p-3 sm:p-5 lg:p-6">
<div x-data="antiCheatSession('{{ $session->expires_at?->toIso8601String() }}', '{{ route('peserta-assessment.simulations.events.store', $programSimulation) }}', {{ $pageNumber }}, {{ $pageCount }})" class="mx-auto min-w-0 max-w-7xl space-y-5">
    <div x-show="!secureMode" x-cloak class="fixed inset-0 z-99998 flex items-center justify-center bg-gray-900/90 p-4 backdrop-blur-md"><div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl"><h2 class="text-lg font-semibold text-gray-800" x-text="violations ? 'Halaman Pengerjaan Dikunci' : 'Aktifkan Mode Fullscreen'"></h2><p class="mt-2 text-sm leading-6 text-gray-500" x-text="securityMessage"></p><div x-show="violations > 0" class="mt-4 rounded-xl px-4 py-3 text-sm font-medium" :class="violations >= 3 ? 'bg-error-50 text-error-700' : 'bg-warning-50 text-warning-700'"><span x-text="violations"></span> aktivitas tercatat<span x-show="violations >= 3"> · perlu ditinjau asesor</span></div><p x-show="fullscreenError" x-text="fullscreenError" class="mt-3 text-sm text-error-500"></p><button type="button" @click="enableFullscreen" class="mt-6 w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Kembali ke Fullscreen & Lanjutkan</button></div></div>
    <div class="sticky top-3 z-30 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="min-w-0">
                <div class="flex items-center gap-2"><p class="text-xs font-medium uppercase text-brand-500">Halaman <span x-text="currentPage"></span> dari {{ $pageCount }}</p><span class="h-1 w-1 rounded-full bg-gray-300"></span><p class="text-xs text-gray-500">{{ $programSimulation->scenario->duration_minutes }} menit</p></div>
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
    @foreach($materials as $index => $pageMaterial)
        @php($currentNumber = $index + 1)
        <section x-show="currentPage === {{ $currentNumber }}" x-cloak class="space-y-5">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800"><div><span class="text-xs font-medium text-brand-500">Materi {{ $currentNumber }}</span><h2 class="mt-1 font-semibold text-gray-800 dark:text-white/90">Uraian Simulasi</h2></div><span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300"><svg class="size-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6.5 9V6.75a3.5 3.5 0 0 1 7 0V9M5.75 9h8.5v7h-8.5V9Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>Hanya baca</span></div>
                <iframe title="Materi PDF {{ $currentNumber }} — hanya baca" src="{{ route('peserta-assessment.simulations.material.pdf', [$programSimulation, $pageMaterial]) }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" class="h-[72vh] min-h-[640px] w-full select-none bg-gray-100" referrerpolicy="same-origin"></iframe>
                @if($pageMaterial->content)<div class="border-t border-gray-200 px-5 py-4 text-sm text-gray-500 dark:border-gray-800">{{ $pageMaterial->content }}</div>@endif
            </div>
            <form method="POST" action="{{ route('peserta-assessment.simulations.material.save', [$programSimulation, $currentNumber]) }}" @if($currentNumber < $pageCount) @submit.prevent="saveAndContinue($event, {{ $currentNumber + 1 }})" @endif class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                @csrf @method('PUT')
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Jawaban Materi {{ $currentNumber }} @if($pageMaterial->is_required)<span class="text-error-500">*</span>@endif</label>
                <x-forms.rich-text-editor name="response" :value="old('response', $responses[$currentNumber] ?? '')" :required="$pageMaterial->is_required" />
                @if($pageNumber === $currentNumber) @error('response')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror @endif
                <div class="mt-5 flex items-center justify-between">
                    <button type="button" @click="goToPage(Math.max(1, currentPage - 1))" :disabled="currentPage === 1" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300">Sebelumnya</button>
                    <button type="submit" @if($currentNumber === $pageCount) name="submit_after_save" value="1" @click.prevent="$dispatch('confirm-dialog', { title: 'Simpan dan kumpulkan?', message: 'Jawaban materi terakhir akan disimpan bersama seluruh jawaban, lalu tidak dapat diubah kembali.', confirmLabel: 'Ya, Kumpulkan', onConfirm: () => $el.form.requestSubmit($el) })" @endif :disabled="saving" class="rounded-lg px-5 py-2.5 text-sm font-medium text-white disabled:cursor-wait disabled:opacity-60 {{ $currentNumber === $pageCount ? 'bg-success-500 hover:bg-success-600' : 'bg-brand-500 hover:bg-brand-600' }}"><span x-text="saving && currentPage === {{ $currentNumber }} ? 'Menyimpan...' : '{{ $currentNumber === $pageCount ? 'Simpan & Kumpulkan' : 'Simpan & Berikutnya' }}'"></span></button>
                </div>
            </form>
        </section>
    @endforeach
</div>
</main>
@endsection

@push('scripts')
<script>
    function antiCheatSession(expiresAt, endpoint, initialPage, totalPages) {
        return {
            currentPage: initialPage,
            totalPages,
            saving: false,
            expiresAt: new Date(expiresAt).getTime(),
            initialRemaining: Math.max(1, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)),
            remaining: Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)),
            violations: 0,
            fullscreenActivated: false,
            secureMode: false,
            fullscreenError: '',
            sequence: 0,
            lastEvents: {},
            lockReason: '',
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
            get securityMessage() {
                if (!this.fullscreenActivated) return 'Assessment wajib dikerjakan dalam mode fullscreen.';
                if (this.lockReason === 'tab_hidden') return 'Perpindahan tab terdeteksi. Timer tetap berjalan dan materi disembunyikan sampai Anda melanjutkan dalam fullscreen.';
                return 'Mode fullscreen terhenti. Timer tetap berjalan dan materi disembunyikan sampai fullscreen diaktifkan kembali.';
            },
            init() {
                setInterval(() => { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)) }, 1000);
                document.addEventListener('fullscreenchange', () => {
                    if (this.fullscreenActivated && !document.fullscreenElement) {
                        this.secureMode = false;
                        setTimeout(() => { if (!document.hidden) { this.lockReason = 'fullscreen_exit'; this.report('fullscreen_exit'); } }, 250);
                    }
                });
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden && this.fullscreenActivated) { this.secureMode = false; this.lockReason = 'tab_hidden'; this.report('tab_hidden'); }
                });
            },
            async enableFullscreen() {
                try { if (!document.fullscreenElement) await document.documentElement.requestFullscreen(); this.fullscreenActivated = true; this.secureMode = true; this.lockReason = ''; this.fullscreenError = ''; }
                catch (_) { this.fullscreenError = 'Browser menolak fullscreen. Izinkan fullscreen pada pengaturan situs lalu coba kembali.'; this.report('fullscreen_denied'); }
            },
            goToPage(page, url = null) {
                this.currentPage = Math.max(1, Math.min(this.totalPages, page));
                const nextUrl = url || `${window.location.pathname.replace(/\/\d+$/, '')}/${this.currentPage}`;
                window.history.replaceState({}, '', nextUrl);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            async saveAndContinue(event, nextPage) {
                if (this.saving) return;

                const form = event.currentTarget;
                const formData = new FormData(form);
                this.saving = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: formData,
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const message = data.errors?.response?.[0] || data.message || 'Jawaban belum dapat disimpan.';
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', title: 'Jawaban belum tersimpan', message } }));
                        return;
                    }

                    this.goToPage(data.next_page || nextPage, data.next_url);
                    window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', title: 'Jawaban tersimpan', message: 'Materi berikutnya siap dikerjakan.' } }));
                } catch (_) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', title: 'Koneksi bermasalah', message: 'Jawaban belum tersimpan. Periksa koneksi lalu coba kembali.' } }));
                } finally {
                    this.saving = false;
                }
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
