@php
    $notifications = collect([
        session('success') ? ['type' => 'success', 'title' => 'Berhasil', 'message' => session('success')] : null,
        session('error') ? ['type' => 'error', 'title' => 'Proses gagal', 'message' => session('error')] : null,
        session('warning') ? ['type' => 'warning', 'title' => 'Perlu diperhatikan', 'message' => session('warning')] : null,
        session('status') ? ['type' => 'info', 'title' => 'Informasi', 'message' => session('status')] : null,
        $errors->any() ? [
            'type' => 'error',
            'title' => 'Data belum dapat disimpan',
            'message' => $errors->count() === 1
                ? $errors->first()
                : 'Periksa kembali '.min($errors->count(), 5).' kolom yang masih bermasalah.',
        ] : null,
    ])->filter()->values();
@endphp

@if ($notifications->isNotEmpty())
    <div
        class="pointer-events-none fixed inset-x-4 top-4 z-[100000] flex flex-col items-end gap-3 sm:left-auto sm:right-6 sm:top-6 sm:w-full sm:max-w-sm"
        aria-live="polite"
        aria-atomic="true"
    >
        @foreach ($notifications as $notification)
            @php
                $isSuccess = $notification['type'] === 'success';
                $isError = $notification['type'] === 'error';
                $isWarning = $notification['type'] === 'warning';
                $accent = $isSuccess ? 'bg-success-500' : ($isError ? 'bg-error-500' : ($isWarning ? 'bg-warning-500' : 'bg-brand-500'));
                $iconBox = $isSuccess ? 'bg-success-50 text-success-600' : ($isError ? 'bg-error-50 text-error-600' : ($isWarning ? 'bg-warning-50 text-warning-600' : 'bg-brand-50 text-brand-600'));
            @endphp
            <div
                x-data="{ open: true, timer: null }"
                x-init="timer = setTimeout(() => open = false, {{ $isError ? 7000 : 5000 }})"
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-[-12px] opacity-0 sm:translate-x-8 sm:translate-y-0"
                x-transition:enter-end="translate-x-0 translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-8 opacity-0"
                @mouseenter="clearTimeout(timer)"
                @mouseleave="timer = setTimeout(() => open = false, 2500)"
                role="{{ $isError ? 'alert' : 'status' }}"
                class="pointer-events-auto relative w-full overflow-hidden rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-theme-xl backdrop-blur"
            >
                <div class="absolute inset-y-0 left-0 w-1 {{ $accent }}"></div>
                <div class="flex items-start gap-3 pl-1">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $iconBox }}">
                        @if ($isSuccess)
                            <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 10 3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @elseif ($isError)
                            <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6v4m0 4h.01M17.5 10a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        @elseif ($isWarning)
                            <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 7v3m0 3h.01M8.7 3.8 2.6 14.4A1.5 1.5 0 0 0 3.9 16.7h12.2a1.5 1.5 0 0 0 1.3-2.3L11.3 3.8a1.5 1.5 0 0 0-2.6 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        @else
                            <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 9v5m0-8h.01M17.5 10a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="text-sm font-semibold text-gray-900">{{ $notification['title'] }}</p>
                        <p class="mt-1 text-sm leading-5 text-gray-600">{{ $notification['message'] }}</p>
                        @if ($isError && $errors->count() > 1)
                            <ul class="mt-2 list-inside list-disc space-y-0.5 text-xs text-gray-500">
                                @foreach ($errors->all() as $message)
                                    @if ($loop->iteration <= 5)<li>{{ $message }}</li>@endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <button type="button" @click="open = false" class="-mr-1 -mt-1 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Tutup notifikasi">
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 6 8 8m0-8-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <div class="absolute inset-x-0 bottom-0 h-0.5 bg-gray-100"><div class="h-full {{ $accent }} motion-safe:animate-[toast-progress_5s_linear_forwards]"></div></div>
            </div>
        @endforeach
    </div>
@endif
