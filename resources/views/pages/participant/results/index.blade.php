@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Hasil & Rekomendasi" />
<div class="mb-5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">Hasil ditampilkan setelah asesor memfinalisasi penilaian. Penilaian yang masih berupa draft tidak dipublikasikan.</div>
<div class="space-y-6">
    @forelse($participations as $participation)
        @php($finalReviewCount = $participation->sessions->sum(fn($session) => $session->reviews->count()))
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div><h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $participation->program->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $participation->categoryLabel() }}</p></div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-medium {{ $finalReviewCount ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' }}">{{ $finalReviewCount ? $finalReviewCount.' penilaian final' : 'Menunggu penilaian' }}</span>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($participation->sessions->filter(fn($session) => $session->reviews->isNotEmpty()) as $session)
                    <div class="px-5 py-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-medium uppercase text-brand-500">Simulasi {{ $session->programSimulation->scenario->type->sequence }}</p><h3 class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $session->programSimulation->scenario->simulationThreePackageLabel() ?? $session->programSimulation->scenario->type->name }}</h3></div><span class="text-xs text-gray-500">Dinilai {{ $session->reviews->max('reviewed_at')?->format('d M Y H:i') }}</span></div>
                        <div class="mt-3 grid gap-3">
                            @foreach($session->reviews as $review)
                                @php($labels = ['recommended' => 'Direkomendasikan', 'recommended_with_development' => 'Direkomendasikan dengan Pengembangan', 'considered' => 'Dipertimbangkan', 'not_recommended' => 'Belum Direkomendasikan'])
                                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"><span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $labels[$review->recommendation] ?? 'Rekomendasi tersedia' }}</span><span class="text-xs text-gray-500">Asesor: {{ $review->assessor->name }}</span></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $review->assessment_notes ?: 'Tidak ada catatan tambahan.' }}</p></div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">Belum ada hasil yang difinalisasi untuk program ini.</div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-gray-200 bg-white px-6 py-16 text-center dark:border-gray-800 dark:bg-white/[0.03]"><h2 class="font-semibold text-gray-800 dark:text-white/90">Belum ada hasil assessment</h2><p class="mt-2 text-sm text-gray-500">Hasil akan muncul setelah Anda mengikuti program dan asesor menyelesaikan penilaian.</p></div>
    @endforelse
</div>
@endsection
