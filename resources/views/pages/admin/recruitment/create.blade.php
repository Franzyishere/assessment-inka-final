@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Buat Batch Rekrutmen" />
<div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
    <div class="mb-6"><h2 class="text-lg font-semibold text-gray-900">Informasi Batch</h2><p class="mt-1 text-sm text-gray-500">Buat wadah pelaksanaan rekrutmen sebelum mengimpor peserta dan mengatur psikotes.</p></div>
    <form method="POST" action="{{ route('admin.recruitment.store') }}">@csrf @include('pages.admin.recruitment._form')
        <div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.recruitment.index') }}" class="crud-btn-secondary">Batal</a><button class="crud-btn-primary">Simpan Batch</button></div>
    </form>
</div>
@endsection
