@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Atur Program Assessment" />

@if (session('success'))
    <div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
@endif

@php
    $initialParticipants = array_map('intval', old('participant_ids', $selectedParticipants));
    $allAssessorIds = $assessors->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    $initialAssessors = array_map('intval', old('assessor_ids', $selectedAssessors));
@endphp

<form method="POST" action="{{ route('admin.assessment-programs.setup.update', $program) }}"
    x-data="{
        selectedAssessors: @js($initialAssessors),
        allAssessorIds: @js($allAssessorIds),
        assessorValidationOpen: @js($errors->has('assessor_ids')),
        selectedParticipants: @js($initialParticipants),
        participantSearch: '',
        participantMatches(searchable) {
            return searchable.includes(this.participantSearch.trim().toLowerCase())
        }
    }" @submit="if (selectedAssessors.length === 0) { $event.preventDefault(); assessorValidationOpen = true }" class="space-y-6">
    @csrf @method('PUT')

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $program->name }}</h2><p class="mt-1 text-sm text-gray-500">Tentukan tim asesor dan peserta. Empat simulasi bawaan digunakan otomatis.</p></div>
            <div class="rounded-lg bg-brand-50 px-4 py-2 text-sm font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">4 simulasi otomatis</div>
        </div>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex items-center gap-3"><span class="inline-flex size-7 items-center justify-center rounded-full bg-success-500 text-sm font-semibold text-white">✓</span><div><h3 class="font-semibold text-gray-800 dark:text-white/90">Simulasi Program Otomatis</h3><p class="mt-1 text-sm text-gray-500">PA, LGD, Simulasi 3 sesuai kategori, dan Presentasi langsung dipasang saat pengaturan disimpan.</p></div></div>
        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">@foreach($scenarios->unique('simulation_type_id') as $scenario)<div class="rounded-lg bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">{{ $scenario->type->name }}</div>@endforeach</div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-3"><span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">1</span><div><h3 class="font-semibold text-gray-800 dark:text-white/90">Tim Asesor Program</h3><p class="mt-1 text-sm text-gray-500"><span x-text="selectedAssessors.length"></span> asesor akan ditugaskan ke seluruh simulasi.</p></div></div>@if($assessors->isNotEmpty())<label class="flex cursor-pointer items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 dark:bg-white/5 dark:text-gray-300"><input type="checkbox" :checked="selectedAssessors.length === allAssessorIds.length" @change="selectedAssessors = $event.target.checked ? [...allAssessorIds] : []" class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">Pilih semua asesor</label>@endif</div>
        <div class="grid max-h-72 grid-cols-1 gap-2 overflow-y-auto md:grid-cols-2 xl:grid-cols-3">@forelse($assessors as $assessor)<label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5"><input type="checkbox" name="assessor_ids[]" value="{{ $assessor->id }}" x-model.number="selectedAssessors" class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"><span class="min-w-0"><span class="block truncate text-sm font-medium text-gray-700 dark:text-gray-300">{{ $assessor->name }}</span><span class="block truncate text-xs text-gray-500">{{ $assessor->email }}</span></span></label>@empty<p class="text-sm text-warning-600">Belum ada akun asesor. Buat akun melalui Super Admin.</p>@endforelse</div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div class="flex items-center gap-3"><span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">2</span><div><h3 class="font-semibold text-gray-800 dark:text-white/90">Peserta Assessment</h3><p class="mt-1 text-sm text-gray-500"><span x-text="selectedParticipants.length"></span> peserta dipilih.</p></div></div><div class="relative w-full sm:max-w-xs"><svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" viewBox="0 0 20 20" fill="none"><circle cx="9" cy="9" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="m13 13 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg><input type="search" x-model.debounce.200ms="participantSearch" placeholder="Cari nama atau email..." class="h-10 w-full rounded-lg border border-gray-300 bg-transparent pl-9 pr-3 text-sm text-gray-700 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-gray-300"></div></div>
        <div class="grid max-h-[430px] grid-cols-1 gap-2 overflow-y-auto md:grid-cols-2 xl:grid-cols-3">
            @forelse ($participants as $participant)
                <div x-show="participantMatches(@js(Str::lower($participant->name.' '.$participant->email)))" class="rounded-xl border border-gray-200 p-3 dark:border-gray-800"><label class="flex cursor-pointer items-center gap-3"><input type="checkbox" name="participant_ids[]" value="{{ $participant->id }}" x-model.number="selectedParticipants" class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"><span class="min-w-0"><span class="block truncate text-sm font-medium text-gray-700 dark:text-gray-300">{{ $participant->name }}</span><span class="block truncate text-xs text-gray-500">{{ $participant->email }}</span></span></label><select name="participant_categories[{{ $participant->id }}]" :disabled="!selectedParticipants.includes({{ (int) $participant->id }})" class="mt-3 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-xs text-gray-700 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400 dark:border-gray-700 dark:text-gray-300 dark:disabled:bg-white/5"><option value="">Tujuan assessment belum ditentukan</option>@foreach($assessmentCategories as $value => $label)<option value="{{ $value }}" @selected(old("participant_categories.{$participant->id}", $selectedParticipantCategories[$participant->id] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            @empty
                <p class="col-span-full text-sm text-gray-500">Belum ada akun peserta assessment.</p>
            @endforelse
        </div>
        @error('participant_ids')<p class="mt-4 text-sm text-error-500">{{ $message }}</p>@enderror
    </section>

    @if ($errors->any())<div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">Pengaturan belum tersimpan. Periksa kembali pilihan yang ditandai.</div>@endif
    <div class="flex flex-wrap justify-end gap-3"><a href="{{ route('admin.assessment-programs.index') }}" class="crud-btn-secondary">Kembali</a><button class="crud-btn-primary">Simpan Pengaturan</button></div>

    <div x-show="assessorValidationOpen" x-cloak @keydown.escape.window="assessorValidationOpen = false" class="fixed inset-0 z-99999 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="assessor-validation-title">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="assessorValidationOpen = false"></div>
        <div x-show="assessorValidationOpen" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-error-50 text-error-500 dark:bg-error-500/10"><svg class="size-6" viewBox="0 0 24 24" fill="none"><path d="M12 8v5m0 3.5v.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
            <h3 id="assessor-validation-title" class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">Tim Asesor Belum Dipilih</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pilih minimal satu asesor sebelum menyimpan pengaturan program.</p>
            <button type="button" @click="assessorValidationOpen = false" class="crud-btn-primary mt-6 w-full">Kembali Pilih Asesor</button>
        </div>
    </div>
</form>
@endsection
