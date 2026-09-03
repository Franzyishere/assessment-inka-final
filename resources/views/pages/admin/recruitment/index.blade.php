@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Rekrutmen" />
<div x-data="{ deleting: null }" class="rounded-2xl border border-gray-200 bg-white">
    <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h2 class="font-semibold text-gray-900">Batch Rekrutmen</h2><p class="mt-1 text-sm text-gray-500">Kelola jadwal, akun peserta, dan persiapan psikotes per batch.</p></div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.recruitment.psychotests.index') }}" class="crud-btn-secondary">Master Psikotes</a>
            <a href="{{ route('admin.recruitment.create') }}" class="crud-btn-primary">Buat Batch</a>
        </div>
    </div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>@foreach(['Batch','Periode','Peserta','Status','Aksi'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100">@forelse($batches as $batch)
            @php($statusLabel = ['draft'=>'Draft','scheduled'=>'Terjadwal','active'=>'Aktif','completed'=>'Selesai','cancelled'=>'Dibatalkan'][$batch->status] ?? ucfirst($batch->status))
            <tr class="transition hover:bg-gray-50/70"><td class="px-5 py-4"><div class="font-medium text-gray-900">{{ $batch->name }}</div><div class="mt-0.5 text-xs text-gray-500">Dibuat {{ $batch->created_at->format('d M Y') }}</div></td><td class="px-5 py-4 text-sm text-gray-600">{{ $batch->starts_at?->format('d M Y H:i') ?? 'Belum diatur' }}<br>{{ $batch->ends_at?->format('d M Y H:i') ?? '-' }}</td><td class="px-5 py-4 text-sm text-gray-700">{{ $batch->participants_count }}</td><td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $statusLabel }}</span></td><td class="px-5 py-4"><div class="crud-actions"><a href="{{ route('admin.recruitment.show', $batch) }}" class="crud-btn-soft-brand">Kelola</a><a href="{{ route('admin.recruitment.edit', $batch) }}" class="crud-btn-soft-neutral">Edit</a><button type="button" @click="deleting={name:@js($batch->name),action:@js(route('admin.recruitment.destroy',$batch))}" class="crud-btn-soft-danger">Hapus</button></div></td></tr>
        @empty<tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada batch rekrutmen.</td></tr>@endforelse</tbody></table></div>
    @if($batches->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $batches->links() }}</div>@endif
    <div x-show="deleting" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center p-4"><div class="absolute inset-0 bg-gray-900/50" @click="deleting=null"></div><div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl"><h3 class="text-lg font-semibold">Hapus Batch Rekrutmen?</h3><p class="mt-2 text-sm text-gray-500">Batch <span class="font-medium" x-text="deleting?.name"></span> hanya dapat dihapus jika masih draft dan belum memiliki peserta.</p><div class="mt-6 flex justify-end gap-3"><button @click="deleting=null" class="crud-btn-secondary">Batal</button><form method="POST" :action="deleting?.action">@csrf @method('DELETE')<button class="crud-btn-danger">Ya, Hapus</button></form></div></div></div>
</div>
@endsection
