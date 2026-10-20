@extends('layouts.app')

@push('styles')
<style>
    .archive-disclosure > summary { list-style: none; }
    .archive-disclosure > summary::-webkit-details-marker { display: none; }
    .archive-disclosure[open] > summary .archive-chevron { transform: rotate(180deg); }
</style>
@endpush

@section('content')
<x-common.page-breadcrumb pageTitle="Hasil Program Assessment" />

<section class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Arsip Assessment</p>
            <h1 class="mt-2 break-words text-xl font-semibold text-gray-900 sm:text-2xl">{{ $program->name }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-500">
                <span>{{ $program->starts_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') ?? 'Jadwal belum ditentukan' }}{{ $program->starts_at ? ' WIB' : '' }}</span>
                <span class="inline-flex items-center gap-2"><span class="size-1.5 rounded-full {{ $program->archived_at ? 'bg-gray-400' : 'bg-success-500' }}"></span>{{ $program->archived_at ? 'Program diarsipkan' : 'Program operasional' }}</span>
            </div>
        </div>
        <a class="crud-btn-secondary shrink-0 self-start" href="{{ route('admin.result-archives.index') }}">Kembali ke Arsip</a>
    </div>
</section>

<div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="shrink-0">
        <h2 class="font-semibold text-gray-900">Hasil Peserta <span class="ml-1 text-sm font-normal text-gray-500">({{ $participants->total() }})</span></h2>
        <p class="mt-1 text-sm text-gray-500">Jawaban dan pembagian arsip per simulasi.</p>
    </div>
    <x-common.search-form :action="route('admin.result-archives.show', $program->id)" placeholder="Cari nama atau email peserta..." />
</div>

<div class="space-y-3">
@forelse($participants as $participant)
    @php
        $sessions = $participant->sessions->sortBy(fn ($session) => $session->programSimulation->scenario->type->sequence);
        $participantFirstName = preg_split('/\s+/u', trim($participant->user->name), 2)[0];
        $submittedCount = $sessions->where('status', 'submitted')->count();
        $assignedCount = $sessions->filter(fn ($session) => $session->status === 'submitted' && $session->archiveGrant)->count();
    @endphp
    <details class="archive-disclosure overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <summary class="flex cursor-pointer items-center gap-3 px-4 py-4 outline-none hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500 sm:gap-4 sm:px-5">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600">{{ mb_strtoupper(mb_substr($participant->user->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <h3 class="break-words text-sm font-semibold text-gray-900 sm:text-base">{{ $participant->user->name }}</h3>
                <p class="mt-0.5 break-all text-xs text-gray-500">{{ $participant->user->email }}</p>
            </div>
            <div class="hidden shrink-0 text-right text-xs text-gray-500 sm:block">
                <p class="font-medium text-gray-700">{{ $submittedCount }} simulasi dikumpulkan</p>
                <p class="mt-1">{{ $assignedCount }} arsip dibagikan</p>
            </div>
            <svg class="archive-chevron size-4 shrink-0 text-gray-400 transition-transform" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </summary>

        <div class="border-t border-gray-100 bg-gray-50/60 p-4 sm:p-5">
            <div class="mb-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-500">
                <span>Tujuan: <span class="font-medium text-gray-700">{{ $participant->categoryLabel() }}</span></span>
                @if($participant->user->employee_number)<span>NIP: <span class="font-medium text-gray-700">{{ $participant->user->employee_number }}</span></span>@endif
                <span class="sm:hidden">{{ $submittedCount }} dikumpulkan · {{ $assignedCount }} dibagikan</span>
            </div>
            <div class="space-y-4">
            @forelse($sessions as $session)
                @php
                    $scenario = $session->programSimulation->scenario;
                    $statusLabel = ['submitted' => 'Dikumpulkan', 'in_progress' => 'Dalam pengerjaan', 'expired' => 'Waktu habis'][$session->status] ?? 'Belum dikumpulkan';
                    $statusClass = match ($session->status) {
                        'submitted' => 'bg-success-50 text-success-700',
                        'in_progress' => 'bg-warning-50 text-warning-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <section id="session-{{ $session->id }}" class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-5">
                        <div class="min-w-0 flex-1">
                            <h4 class="break-words text-sm font-semibold text-gray-900">{{ $scenario->type->name }} - {{ $participantFirstName }}</h4>
                            @if($scenario->simulationThreePackageLabel())<p class="mt-1 text-xs text-gray-500">{{ $scenario->simulationThreePackageLabel() }}</p>@endif
                            <p class="mt-1 text-xs text-gray-500">{{ $session->submitted_at ? 'Dikumpulkan '.$session->submitted_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : 'Belum ada waktu pengumpulan' }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                    </header>

                    <div class="grid min-w-0 lg:grid-cols-[minmax(0,1fr)_18rem]">
                        <div class="min-w-0 space-y-3 p-4 sm:p-5">
                            @forelse($session->submissions->sortByDesc('revision') as $submission)
                                @if($submission->storage_path)
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M14 3H6v18h12V7l-4-4Z"/><path d="M14 3v5h5M9 12h6M9 16h6"/></svg></span>
                                            <div class="min-w-0"><p class="break-all text-sm font-medium text-gray-800">{{ $submission->original_filename ?: 'Presentasi PDF' }}</p><p class="mt-0.5 text-xs text-gray-500">Berkas peserta</p></div>
                                        </div>
                                        <a class="crud-btn-soft-neutral shrink-0" href="{{ route('admin.result-archives.file', [$program->id, $submission]) }}" aria-label="Unduh {{ $submission->original_filename ?: 'presentasi PDF' }}">Unduh</a>
                                    </div>
                                @else
                                    <details class="archive-disclosure rounded-lg border border-gray-200">
                                        <summary class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <span>Lihat jawaban @if($session->submissions->count() > 1)<span class="ml-1 text-xs font-normal text-gray-500">Revisi {{ $submission->revision }}</span>@endif</span>
                                            <svg class="archive-chevron size-4 shrink-0 text-gray-400 transition-transform" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </summary>
                                        <div class="max-h-[36rem] min-w-0 overflow-auto border-t border-gray-100 p-4 text-sm leading-7 text-gray-900">
                                            @php($answers = json_decode($submission->response_text ?? '', true))
                                            @if(is_array($answers))
                                                <div class="space-y-5">
                                                @foreach($answers as $number => $answer)
                                                    <div class="min-w-0">
                                                        <h5 class="mb-3 border-b border-gray-100 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Jawaban {{ $number }}</h5>
                                                        @if(!empty($submission->diagrams[$number]))<x-common.answer-diagram :value="$submission->diagrams[$number]" />@endif
                                                        <div class="tiptap-answer-content break-words">{!! \App\Support\RichTextSanitizer::sanitize(is_string($answer) ? $answer : '') !!}</div>
                                                    </div>
                                                @endforeach
                                                </div>
                                            @else
                                                <div class="tiptap-answer-content break-words">{!! \App\Support\RichTextSanitizer::sanitize($submission->response_text) !!}</div>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            @empty
                                <p class="py-2 text-sm leading-6 text-gray-500">{{ $scenario->type->delivery_mode === 'assessor_observation' ? 'Sesi LGD tidak memiliki lembar jawaban tertulis.' : 'Belum ada jawaban atau berkas yang tersimpan.' }}</p>
                            @endforelse

                            @if($session->reviews->isNotEmpty())
                                <details class="rounded-lg border border-gray-200 px-4 py-3">
                                    <summary class="cursor-pointer text-sm font-medium text-gray-600">Catatan assessment tersimpan</summary>
                                    <div class="mt-3 space-y-3">
                                    @foreach($session->reviews as $review)
                                        <div class="border-t border-gray-100 pt-3 text-sm">
                                            <p class="font-medium text-gray-800">{{ $review->assessor?->name ?? 'Asesor' }} · {{ $review->status === 'submitted' ? 'Final' : 'Draft' }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ ['recommended' => 'Direkomendasikan', 'considered' => 'Dipertimbangkan', 'not_recommended' => 'Tidak direkomendasikan'][$review->recommendation] ?? 'Belum ada rekomendasi' }}</p>
                                            <p class="mt-2 whitespace-pre-wrap break-words leading-6 text-gray-700">{{ $review->assessment_notes }}</p>
                                        </div>
                                    @endforeach
                                    </div>
                                </details>
                            @endif
                        </div>

                        <aside class="min-w-0 border-t border-gray-100 bg-gray-50/60 p-4 sm:p-5 lg:border-l lg:border-t-0" aria-label="Pembagian arsip {{ $scenario->type->name }}">
                            <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pembagian Arsip</h5>
                            @if($session->status === 'submitted')
                                <p class="mt-2 text-sm {{ $session->archiveGrant ? 'font-medium text-gray-800' : 'text-gray-500' }}">{{ $session->archiveGrant?->assessor?->name ?? 'Belum dibagikan' }}</p>
                                <form method="POST" action="{{ route('admin.result-archives.assign', [$program->id, $session]) }}" class="mt-4 space-y-3">
                                    @csrf @method('PUT')
                                    <label class="block text-xs font-medium text-gray-600" for="assessor-{{ $session->id }}">Asesor penerima</label>
                                    <select id="assessor-{{ $session->id }}" name="assessor_id" class="h-10 w-full min-w-0 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-500/10">
                                        <option value="">Belum dibagikan / cabut akses</option>
                                        @foreach($assessors as $assessor)
                                            <option value="{{ $assessor->id }}" @selected($session->archiveGrant?->assessor_id === $assessor->id)>{{ $assessor->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex justify-end"><button class="crud-btn-primary">Simpan Pembagian</button></div>
                                </form>
                            @else
                                <p class="mt-2 text-sm leading-6 text-gray-500">Tersedia setelah peserta mengumpulkan simulasi.</p>
                            @endif
                        </aside>
                    </div>
                </section>
            @empty
                <p class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">Peserta belum memulai simulasi.</p>
            @endforelse
            </div>
        </div>
    </details>
@empty
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-12 text-center">
        <h3 class="font-semibold text-gray-800">{{ request()->filled('search') ? 'Peserta tidak ditemukan' : 'Belum ada peserta' }}</h3>
        <p class="mt-2 text-sm text-gray-500">{{ request()->filled('search') ? 'Coba gunakan nama atau email lainnya.' : 'Hasil akan tersedia setelah peserta ditambahkan ke program.' }}</p>
    </div>
@endforelse
</div>
<div class="mt-5">{{ $participants->links() }}</div>
@endsection
