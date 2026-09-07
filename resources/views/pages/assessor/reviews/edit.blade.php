@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Penilaian Simulasi" />
@php($isObservation = $session->programSimulation->scenario->type->delivery_mode === 'assessor_observation')
<div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
    <section class="space-y-5 xl:col-span-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="text-xs font-medium uppercase text-brand-500">{{ $session->programSimulation->scenario->type->name }}</span>
            <h1 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $session->programSimulation->scenario->type->name }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ $session->participant->user->name }} · {{ $session->participant->user->email }}</p>
        </div>
        @if($session->events->isNotEmpty())
            <div class="rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-500/30 dark:bg-error-500/10"><h2 class="font-semibold text-error-700 dark:text-error-400">Aktivitas Sesi Tercatat</h2><p class="mt-1 text-sm text-error-600/80">Gunakan informasi ini sebagai konteks, bukan keputusan otomatis.</p><div class="mt-4 flex flex-wrap gap-2">@foreach($session->events->groupBy('event_type') as $type => $events)<span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-error-700 dark:bg-gray-900">{{ \App\Models\SimulationSessionEvent::LABELS[$type] ?? ucwords(str_replace('_', ' ', $type)) }}: {{ $events->count() }}</span>@endforeach</div></div>
        @endif

        @if($submission?->storage_path)
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><div class="flex items-center justify-between p-5"><div><h2 class="font-semibold text-gray-800 dark:text-white/90">File Presentasi</h2><p class="mt-1 text-xs text-gray-500">{{ $submission->original_filename }} · {{ number_format($submission->file_size / 1024, 1) }} KB</p></div><div class="flex gap-2"><a target="_blank" href="{{ route('asesor.submissions.preview', $submission) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Fullscreen</a><a href="{{ route('asesor.submissions.download', $submission) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Unduh</a></div></div><iframe title="Preview presentasi" src="{{ route('asesor.submissions.preview', $submission) }}#toolbar=1&navpanes=0" class="h-[70vh] min-h-[600px] w-full border-t border-gray-200 bg-gray-100 dark:border-gray-800"></iframe></div>
        @elseif($session->programSimulation->scenario->materialPages->isNotEmpty())
            @foreach($session->programSimulation->scenario->materialPages as $material)
                <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><div class="flex items-center justify-between p-5"><div><span class="text-xs font-medium text-brand-500">Materi {{ $material->page_order }}</span><h2 class="mt-1 font-semibold text-gray-800 dark:text-white/90">Uraian PDF dan Jawaban</h2></div><a target="_blank" href="{{ route('asesor.materials.preview', [$session->programSimulation, $material]) }}" class="text-sm font-medium text-brand-500">Fullscreen PDF</a></div><iframe title="Materi {{ $material->page_order }}" src="{{ route('asesor.materials.preview', [$session->programSimulation, $material]) }}#toolbar=1&navpanes=0" class="h-[55vh] min-h-[480px] w-full border-y border-gray-200 bg-gray-100 dark:border-gray-800"></iframe><div class="p-5"><p class="text-xs font-medium uppercase text-gray-500">Jawaban peserta</p><div class="tiptap-answer-content mt-3 rounded-xl bg-gray-50 p-4 text-sm leading-7 text-gray-700 dark:bg-white/5 dark:text-gray-300">@php($savedResponse = $responses[$material->page_order] ?? '')@if(str_contains($savedResponse, '<')){!! $savedResponse !!}@elseif($savedResponse){!! nl2br(e($savedResponse)) !!}@else Tidak ada jawaban. @endif</div></div></article>
            @endforeach
        @elseif($submission)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><span class="text-xs font-medium text-brand-500">Respons Kasus</span><div class="mt-4 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-7 text-gray-700 dark:bg-white/5 dark:text-gray-300">{{ $submission->response_text }}</div></article>
        @else
            <article class="rounded-2xl border border-brand-200 bg-brand-50/50 p-5"><span class="text-xs font-semibold uppercase tracking-wide text-brand-600">Leaderless Group Discussion</span><h2 class="mt-2 font-semibold text-gray-900">Tidak ada jawaban tertulis peserta</h2><p class="mt-2 text-sm leading-6 text-gray-600">LGD dinilai berdasarkan pengamatan asesor selama diskusi berlangsung. Masukkan hasil pengamatan pada kolom <strong>Hasil Observasi LGD</strong> di panel penilaian.</p></article>
        @endif
    </section>

    <aside class="xl:col-span-2">
        <form x-ref="reviewForm" method="POST" action="{{ route('asesor.reviews.update', $session) }}" class="sticky top-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            @csrf @method('PUT')
            <h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $isObservation ? 'Hasil Observasi LGD' : 'Penilaian Asesor' }}</h2>
            @if($review?->status === 'submitted')<div class="mt-4 rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">Penilaian telah difinalisasi pada {{ $review->reviewed_at?->format('d M Y H:i') }}.</div>@endif
            <div class="mt-5"><label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Rekomendasi *</label><select name="recommendation" @disabled($review?->status === 'submitted') class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 dark:border-gray-700 dark:text-white/90"><option value="">Pilih rekomendasi</option>@foreach(['recommended' => 'Direkomendasikan', 'recommended_with_development' => 'Direkomendasikan dengan Pengembangan', 'not_recommended' => 'Belum Direkomendasikan'] as $value => $label)<option value="{{ $value }}" @selected(old('recommendation', $review?->recommendation) === $value)>{{ $label }}</option>@endforeach</select>@error('recommendation')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror</div>
            <div class="mt-5"><label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $isObservation ? 'Catatan Hasil Observasi *' : 'Catatan Assessment' }}</label>@if($isObservation)<p class="mb-2 text-xs leading-5 text-gray-500">Catat perilaku, kontribusi, komunikasi, kepemimpinan, dan dinamika peserta selama LGD.</p>@endif<textarea name="assessment_notes" rows="12" @disabled($review?->status === 'submitted') class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 dark:border-gray-700 dark:text-white/90" placeholder="{{ $isObservation ? 'Tuliskan hasil observasi peserta selama proses LGD...' : 'Tuliskan catatan assessment peserta...' }}">{{ old('assessment_notes', $review?->assessment_notes) }}</textarea>@error('assessment_notes')<p class="mt-1 text-xs font-medium text-error-500">{{ $message }}</p>@enderror</div>
            @unless($review?->status === 'submitted')<div class="mt-5 grid grid-cols-2 gap-3"><button name="status" value="draft" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Simpan Draft</button><button x-ref="finalButton" type="submit" name="status" value="submitted" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white" @click.prevent="$dispatch('confirm-dialog', { title: 'Finalisasi penilaian?', message: 'Penilaian yang telah difinalisasi tidak dapat diubah kembali.', confirmLabel: 'Ya, Finalisasi', onConfirm: () => $refs.reviewForm.requestSubmit($refs.finalButton) })">Finalisasi</button></div>@endunless
            <a href="{{ route('asesor.reviews.program', $session->programSimulation->program) }}" class="mt-3 block text-center text-sm text-gray-500">Kembali ke peserta program</a>
        </form>
    </aside>
</div>
@endsection
