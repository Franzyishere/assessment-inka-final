@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Role & Hak Akses" />
<div class="mb-5 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">Hak akses ditetapkan pada middleware server. Halaman ini menjadi referensi resmi cakupan setiap role dan tidak mengubah keamanan melalui browser.</div>
<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    @foreach($permissions as $role => $items)
        @php($labels = ['super_admin'=>'Super Admin','admin'=>'Admin HCGA','asesor'=>'Asesor','peserta_assessment'=>'Peserta Assessment','peserta_rekrutmen'=>'Peserta Rekrutmen'])
        <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between"><h2 class="font-semibold text-gray-800 dark:text-white/90">{{ $labels[$role] }}</h2><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-500 dark:bg-white/10">{{ $role }}</code></div>
            <ul class="mt-4 space-y-3">@foreach($items as $item)<li class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300"><span class="inline-flex size-5 items-center justify-center rounded-full bg-success-50 text-xs text-success-600">✓</span>{{ $item }}</li>@endforeach</ul>
        </section>
    @endforeach
</div>
@endsection
