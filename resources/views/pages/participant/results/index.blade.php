@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Hasil & Rekomendasi" />

<div class="mb-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs"><x-common.search-form :action="route('peserta-assessment.results.index')" placeholder="Cari program atau hasil simulasi..." /></div>

<div class="mb-5 flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-700"><span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-semibold text-white">i</span><p>Hasil hanya ditampilkan setelah asesor memfinalisasi penilaian. Penilaian berstatus draft tidak dipublikasikan.</p></div>

<div class="space-y-6">
    @forelse($participations as $participation)
        @php
            $finalReviewCount = $participation->sessions->sum(fn($session) => $session->reviews->count());
        @endphp
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <header class="flex flex-col gap-3 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Hasil Program</p><h2 class="mt-1 font-semibold text-gray-900">{{ $participation->program->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $participation->categoryLabel() }}</p></div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $finalReviewCount ? 'bg-success-50 text-success-700' : 'bg-warning-50 text-warning-700' }}">{{ $finalReviewCount ? $finalReviewCount.' penilaian final' : 'Menunggu Penilaian' }}</span>
            </header>
            <div class="divide-y divide-gray-100">
                @forelse($participation->sessions->filter(fn($session) => $session->reviews->isNotEmpty()) as $session)
                    <article class="px-5 py-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Simulasi {{ $session->programSimulation->scenario->type->sequence }}</p><h3 class="mt-1 font-semibold text-gray-900">{{ $session->programSimulation->scenario->simulationThreePackageLabel() ?? $session->programSimulation->scenario->type->name }}</h3></div><span class="text-xs text-gray-500">Dinilai {{ $session->reviews->max('reviewed_at')?->format('d M Y, H:i') }}</span></div>
                        <div class="mt-4 grid gap-3">
                            @foreach($session->reviews as $review)
                                @php
                                    $labels = ['recommended' => 'Direkomendasikan', 'recommended_with_development' => 'Direkomendasikan dengan Pengembangan', 'considered' => 'Dipertimbangkan', 'not_recommended' => 'Belum Direkomendasikan'];
                                    $isRecommended = in_array($review->recommendation, ['recommended', 'recommended_with_development'], true);
                                @endphp
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4"><div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $isRecommended ? 'bg-success-100 text-success-800' : 'bg-warning-100 text-warning-800' }}">{{ $labels[$review->recommendation] ?? 'Rekomendasi tersedia' }}</span><span class="text-xs text-gray-500">Asesor: {{ $review->assessor->name }}</span></div><p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $review->assessment_notes ?: 'Tidak ada catatan tambahan.' }}</p></div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center"><h3 class="font-medium text-gray-800">Penilaian belum tersedia</h3><p class="mt-1 text-sm text-gray-500">Asesor masih menyelesaikan penilaian untuk program ini.</p></div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center"><h2 class="font-semibold text-gray-800">Belum ada hasil assessment</h2><p class="mt-2 text-sm text-gray-500">Hasil akan muncul setelah Anda mengikuti program dan asesor menyelesaikan penilaian.</p></div>
    @endforelse
</div>
@endsection
