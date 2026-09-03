@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Import Scoring Key PAPI" />
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div><h2 class="font-semibold text-gray-900">Versi {{ $version->version }}</h2><p class="mt-1 text-sm text-gray-500">Isi mapping berdasarkan arah anak panah pada scoring key resmi. Sistem tidak menebak mapping berdasarkan nomor soal.</p></div>
            <a href="{{ route('admin.recruitment.psychotests.scoring.template') }}" class="crud-btn-secondary">Unduh Template</a>
        </div>
        <div class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-600"><span class="font-semibold text-gray-800">Kode valid:</span> Role: G, L, I, T, V, S, R, D, C, E · Need: N, A, P, X, B, O, Z, K, F, W. Pilihan A dan B pada setiap nomor harus berada dalam kelompok yang sama.</div>
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.recruitment.psychotests.scoring.import.preview', $version) }}" class="mt-5">
            @csrf
            <input type="file" name="scoring_file" accept=".xlsx,.xls,.csv" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            @error('scoring_file')<p class="mt-2 text-sm text-error-600">{{ $message }}</p>@enderror
            <div class="mt-4 flex justify-end"><button class="crud-btn-primary">Buat Preview</button></div>
        </form>
    </div>
    @if(is_array($previewRows))
        @php($invalid = collect($previewRows)->where('valid', false)->count())
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-semibold text-gray-900">Preview {{ count($previewRows) }} Baris</h2><p class="mt-1 text-sm text-gray-500">{{ $invalid ? $invalid.' baris bermasalah dan belum dapat disimpan.' : 'Semua mapping valid secara struktur.' }}</p></div>
            <div class="max-h-[560px] overflow-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="sticky top-0 bg-gray-50"><tr>@foreach(['Nomor','Dimensi A','Dimensi B','Validasi'] as $heading)<th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">@foreach($previewRows as $row)<tr class="{{ $row['valid'] ? '' : 'bg-error-50' }}"><td class="px-4 py-3 font-medium">{{ $row['number'] }}</td><td class="px-4 py-3 text-sm">{{ $row['dimension_a'] }}</td><td class="px-4 py-3 text-sm">{{ $row['dimension_b'] }}</td><td class="px-4 py-3 text-xs {{ $row['valid'] ? 'text-success-700' : 'text-error-700' }}">{{ $row['valid'] ? 'Valid' : implode(' ', $row['errors']) }}</td></tr>@endforeach</tbody></table></div>
            <div class="flex justify-end border-t border-gray-200 px-5 py-4"><form method="POST" action="{{ route('admin.recruitment.psychotests.scoring.import.store', $version) }}">@csrf<button class="crud-btn-primary" @disabled($invalid)>Konfirmasi 180 Mapping</button></form></div>
        </div>
    @endif
</div>
@endsection
