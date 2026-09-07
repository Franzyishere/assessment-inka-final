@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Penugasan Asesor" />
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <div><h2 class="font-semibold text-gray-800 dark:text-white/90">Tim Asesor Program</h2><p class="mt-1 text-sm text-gray-500">Satu tim asesor berlaku untuk seluruh simulasi dalam program.</p></div>
        <form data-live-search method="GET" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row"><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari program..." class="h-9 min-w-56 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700"><select name="program" onchange="this.form.requestSubmit()" class="h-9 min-w-52 rounded-md border border-gray-300 bg-transparent px-3 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300"><option value="">Semua program</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected((string)request('program') === (string)$program->id)>{{ $program->name }}</option>@endforeach</select><button class="crud-btn-secondary">Cari</button>@if(request()->filled('search') || request()->filled('program'))<a href="{{ route('admin.assessor-assignments.index') }}" class="self-center text-xs font-semibold text-gray-500">Reset</a>@endif</form>
    </div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>@foreach(['Program','Simulasi','Peserta','Tim Asesor','Aksi'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">@forelse($assessmentPrograms as $item)@php($team = $item->simulations->pluck('assessorAssignments')->flatten()->pluck('assessor')->filter()->unique('id'))<tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
            <td class="px-5 py-3"><div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $item->name }}</div><div class="text-xs text-gray-500">{{ $item->code }}</div></td>
            <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $item->simulations_count }}</td>
            <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $item->participants_count }}</td>
            <td class="px-5 py-3"><div class="flex max-w-md flex-wrap gap-1.5">@forelse($team as $assessor)<span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $assessor->name }}</span>@empty<span class="text-xs font-medium text-warning-600">Belum ditugaskan</span>@endforelse</div></td>
            <td class="px-5 py-3"><div class="crud-actions"><a href="{{ route('admin.assessor-assignments.edit', $item) }}" class="crud-btn-soft-brand">Atur Tim</a></div></td>
        </tr>@empty<tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada program yang memiliki simulasi.</td></tr>@endforelse</tbody>
    </table></div>
    @if($assessmentPrograms->hasPages())<div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">{{ $assessmentPrograms->links() }}</div>@endif
</div>
@endsection
