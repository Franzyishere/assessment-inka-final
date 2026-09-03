@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Import Peserta Rekrutmen" />
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><h2 class="text-lg font-semibold text-gray-900">{{ $batch->name }}</h2><p class="mt-1 text-sm text-gray-500">Gunakan template resmi dengan kolom nomor_peserta, nama, dan email.</p></div><a href="{{ route('admin.recruitment.import.template') }}" class="crud-btn-secondary shrink-0">Unduh Template Excel</a></div>
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.recruitment.import.preview', $batch) }}" class="mt-6">@csrf
            <label class="mb-1.5 block text-sm font-medium text-gray-700">File Peserta <span class="text-error-500">*</span></label>
            <input type="file" name="participant_file" accept=".xlsx,.xls,.csv" required class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:font-medium">
            <p class="mt-2 text-xs text-gray-500">Format XLSX, XLS, atau CSV. Maksimal 5 MB dan 1.000 baris.</p>
            @error('participant_file')<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
            <div class="mt-5 flex justify-end"><button class="crud-btn-primary">Buat Preview</button></div>
        </form>
    </div>

    @if(is_array($previewRows))
        @php($invalidCount = collect($previewRows)->where('valid', false)->count())
        <div class="rounded-2xl border {{ $invalidCount ? 'border-error-200' : 'border-success-200' }} bg-white">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-semibold text-gray-900">Preview Import</h2><p class="mt-1 text-sm text-gray-500">{{ count($previewRows) }} baris ditemukan · {{ $invalidCount }} baris bermasalah.</p></div></div>
            <div class="max-h-[520px] overflow-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="sticky top-0 bg-gray-50"><tr>@foreach(['Baris','Nomor Peserta','Nama','Email','Validasi'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">@foreach($previewRows as $row)<tr class="{{ $row['valid'] ? '' : 'bg-error-50/50' }}"><td class="px-5 py-3 text-sm text-gray-500">{{ $row['row'] }}</td><td class="px-5 py-3 text-sm font-medium text-gray-900">{{ $row['participant_number'] ?: '-' }}</td><td class="px-5 py-3 text-sm text-gray-700">{{ $row['name'] ?: '-' }}</td><td class="px-5 py-3 text-sm text-gray-500">{{ $row['email'] ?: '-' }}</td><td class="px-5 py-3 text-xs">@if($row['valid'])<span class="font-medium text-success-700">Siap diimport{{ $row['existing'] ? ' · akun tersedia' : '' }}</span>@else<ul class="list-disc space-y-0.5 pl-4 text-error-700">@foreach($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>@endif</td></tr>@endforeach</tbody></table></div>
            <div class="flex flex-col-reverse gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:justify-end"><form method="POST" action="{{ route('admin.recruitment.import.cancel', $batch) }}">@csrf @method('DELETE')<button class="crud-btn-secondary w-full sm:w-auto">Batalkan Preview</button></form><form method="POST" action="{{ route('admin.recruitment.import.store', $batch) }}">@csrf<button class="crud-btn-primary w-full sm:w-auto" @disabled($invalidCount)>Konfirmasi & Import</button></form></div>
        </div>
    @endif
</div>
@endsection
