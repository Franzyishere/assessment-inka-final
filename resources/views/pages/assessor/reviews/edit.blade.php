@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Lembar Jawaban Peserta" />

<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-white/[0.03]">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">{{ $session->programSimulation->scenario->type->name }}</span>
            <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $session->participant->user->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $session->participant->user->email }} · Program: {{ $session->programSimulation->program->name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('asesor.reviews.program', $session->programSimulation->program) }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                <span>Kembali</span>
            </a>
            @if($submission)
                <div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-left">
                    <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        <span>Unduh PDF &amp; Word</span>
                        <svg class="size-4 text-white/80 transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition x-cloak class="absolute right-0 z-50 mt-2 w-52 origin-top-right rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                        <a href="{{ route('asesor.reviews.download', [$session, 'format' => 'pdf']) }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-error-50 hover:text-error-700 dark:text-gray-200 dark:hover:bg-error-500/10 dark:hover:text-error-400">
                            <svg class="size-4.5 text-error-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <path d="M9 15h6M9 11h6M9 19h4"></path>
                            </svg>
                            <span>Unduh PDF (.pdf)</span>
                        </a>
                        <a href="{{ route('asesor.reviews.download', [$session, 'format' => 'word']) }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-brand-50 hover:text-brand-700 dark:text-gray-200 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                            <svg class="size-4.5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <path d="M16 13l-4 4-4-4M12 9v8"></path>
                            </svg>
                            <span>Unduh Word (.doc)</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>



    @if($submission?->storage_path)
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between p-5">
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-white/90">File Presentasi</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ $submission->original_filename }} · {{ number_format($submission->file_size / 1024, 1) }} KB</p>
                </div>
                <div class="flex gap-2">
                    <a target="_blank" href="{{ route('asesor.submissions.preview', $submission) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Fullscreen</a>
                    <a href="{{ route('asesor.submissions.download', $submission) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Unduh</a>
                </div>
            </div>
            <iframe title="Preview presentasi" src="{{ route('asesor.submissions.preview', $submission) }}#toolbar=1&navpanes=0" class="h-[75vh] min-h-[600px] w-full border-t border-gray-200 bg-gray-100 dark:border-gray-800"></iframe>
        </div>
    @elseif($session->programSimulation->scenario->materialPages->isNotEmpty())
        @foreach($session->programSimulation->scenario->materialPages as $material)
            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Jawaban Peserta</p>
                    <div class="tiptap-answer-content mt-3 rounded-xl bg-gray-50 p-5 text-sm leading-7 text-gray-800 dark:bg-white/5 dark:text-gray-200">
                        @php($savedResponse = $responses[$material->page_order] ?? '')
                        @if(str_contains($savedResponse, '<'))
                            {!! $savedResponse !!}
                        @elseif($savedResponse)
                            {!! nl2br(e($savedResponse)) !!}
                        @else
                            <span class="text-gray-400 italic">Tidak ada jawaban tertulis.</span>
                        @endif
                    </div>
                </div>
                <details class="border-t border-gray-200">
                    <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-gray-700">Lihat materi {{ $material->page_order }}</summary>
                <div class="flex items-center justify-between p-5">
                    <div>
                        <span class="text-xs font-semibold text-brand-600 dark:text-brand-400">Materi {{ $material->page_order }}</span>
                        <h2 class="mt-1 font-semibold text-gray-800 dark:text-white/90">Uraian PDF dan Jawaban</h2>
                    </div>
                    <a target="_blank" href="{{ route('asesor.materials.preview', [$session->programSimulation, $material]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                        <span>Fullscreen PDF</span>
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h4a.75.75 0 010 1.5h-4z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
                <iframe loading="lazy" title="Materi {{ $material->page_order }}" src="{{ route('asesor.materials.preview', [$session->programSimulation, $material]) }}#toolbar=1&navpanes=0" class="h-[50vh] min-h-[280px] w-full border-y border-gray-200 bg-gray-100 dark:border-gray-800"></iframe>

                </details>
            </article>
            @if(!empty($submission?->diagrams[$loop->iteration]))
                <x-common.answer-diagram :value="$submission->diagrams[$loop->iteration]" />
            @endif
        @endforeach
    @elseif($submission)
        <article class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">Respons Kasus</span>
            <div class="mt-4 whitespace-pre-line rounded-xl bg-gray-50 p-5 text-sm leading-7 text-gray-800 dark:bg-white/5 dark:text-gray-200">
                {{ $submission->response_text }}
            </div>
        </article>
    @else
        <article class="rounded-2xl border border-brand-200 bg-brand-50/50 p-6">
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Leaderless Group Discussion</span>
            <h2 class="mt-2 text-lg font-semibold text-gray-900">Hasil Observasi LGD</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">LGD dilaksanakan melalui diskusi kelompok langsung. Tidak terdapat dokumen jawaban tertulis yang diunggah oleh peserta.</p>
        </article>
    @endif
    @if($session->events->isNotEmpty())
        <div class="rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-500/30 dark:bg-error-500/10">
            <h2 class="font-semibold text-error-700 dark:text-error-400">Aktivitas Sesi Tercatat</h2>
            <p class="mt-1 text-sm text-error-600/80">Gunakan informasi ini sebagai konteks pelaksanaan assessment.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($session->events->groupBy('event_type') as $type => $events)
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-error-700 dark:bg-gray-900">{{ \App\Models\SimulationSessionEvent::LABELS[$type] ?? ucwords(str_replace('_', ' ', $type)) }}: {{ $events->count() }}</span>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
