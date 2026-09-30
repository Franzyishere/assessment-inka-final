@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Hasil Program Assessment" />
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h2 class="text-xl font-semibold text-gray-900">{{ $program->name }}</h2><p class="text-sm text-gray-600">{{ $program->archived_at ? 'Program diarsipkan' : 'Program operasional' }} · Ringkasan hasil</p></div>
    <a class="crud-btn-secondary" href="{{ route('admin.result-archives.index') }}">Kembali</a>
</div>
<div class="mb-5"><x-common.search-form :action="route('admin.result-archives.show', $program->id)" placeholder="Cari nama atau email peserta..." /></div>
<div class="space-y-4">
@forelse($participants as $participant)
    <details class="rounded-2xl border border-gray-200 bg-white p-5">
        <summary class="cursor-pointer font-semibold text-gray-900">{{ $participant->user->name }} <span class="ml-2 text-sm font-normal text-gray-600">{{ $participant->user->email }} · {{ $participant->categoryLabel() }}</span></summary>
        <div class="mt-5 space-y-5">
        @forelse($participant->sessions->sortBy(fn ($session) => $session->programSimulation->scenario->type->sequence) as $session)
            <section class="rounded-xl border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-900">{{ $session->programSimulation->scenario->type->name }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ ['submitted' => 'Dikumpulkan', 'in_progress' => 'Belum dikumpulkan', 'expired' => 'Waktu habis'][$session->status] ?? $session->status }} · {{ $session->submitted_at?->format('d/m/Y H:i') ?? 'Belum ada waktu pengumpulan' }}</p>
                @forelse($session->submissions->sortByDesc('revision') as $submission)
                    <div class="mt-4 min-w-0 overflow-x-auto rounded-lg bg-gray-50 p-4 text-gray-900">
                        @if($submission->storage_path)
                            <a class="crud-btn-soft-neutral" href="{{ route('admin.result-archives.file', [$program->id, $submission]) }}">Unduh {{ $submission->original_filename ?: 'presentasi PDF' }}</a>
                        @else
                            @php($answers = json_decode($submission->response_text ?? '', true))
                            @if(is_array($answers))
                                @foreach($answers as $number => $answer)
                                    <h4 class="mb-2 mt-3 font-semibold">Jawaban {{ $number }}</h4>
                                    @if(!empty($submission->diagrams[$number]))
                                        <x-common.answer-diagram :value="$submission->diagrams[$number]" />
                                    @endif
                                    <div class="break-words">{!! \App\Support\RichTextSanitizer::sanitize(is_string($answer) ? $answer : '') !!}</div>
                                @endforeach
                            @else
                                <div class="break-words">{!! \App\Support\RichTextSanitizer::sanitize($submission->response_text) !!}</div>
                            @endif
                        @endif
                    </div>
                @empty
                    <p class="mt-3 text-sm text-gray-500">Tidak ada jawaban tertulis atau file. Untuk LGD, lihat observasi asesor.</p>
                @endforelse
                @forelse($session->reviews as $review)
                    <div class="mt-4 border-t border-gray-200 pt-3 text-sm">
                        <p class="font-semibold text-gray-900">{{ $review->assessor?->name ?? 'Asesor' }} · {{ $review->status === 'submitted' ? 'Final' : 'Draft' }}</p>
                        <p class="mt-1">{{ ['recommended' => 'Direkomendasikan', 'considered' => 'Dipertimbangkan', 'not_recommended' => 'Tidak direkomendasikan'][$review->recommendation] ?? 'Belum ada rekomendasi' }}</p>
                        <p class="mt-2 whitespace-pre-wrap break-words text-gray-700">{{ $review->assessment_notes }}</p>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-gray-500">Belum ada penilaian asesor.</p>
                @endforelse
            </section>
        @empty
            <p class="text-sm text-gray-500">Peserta belum memulai simulasi.</p>
        @endforelse
        </div>
    </details>
@empty
    <p class="rounded-xl border bg-white p-6 text-center text-gray-500">Tidak ada peserta yang sesuai.</p>
@endforelse
</div>
<div class="mt-5">{{ $participants->links() }}</div>
@endsection
