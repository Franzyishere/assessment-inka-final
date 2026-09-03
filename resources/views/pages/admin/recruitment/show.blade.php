@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$batch->name" />
<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Jumlah Peserta</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ $participants->total() }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Jadwal Mulai</p><p class="mt-2 text-lg font-semibold text-gray-900">{{ $batch->starts_at?->format('d M Y, H:i') ?? 'Belum diatur' }}</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Status</p><p class="mt-2 text-lg font-semibold text-gray-900">{{ ucfirst($batch->status) }}</p></div>
</div>
<div class="mb-6 grid gap-6 xl:grid-cols-2">
    <div class="rounded-2xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Psikotes Batch</h2>
            <p class="mt-1 text-sm text-gray-500">Instrumen psikotes yang ditetapkan untuk batch ini.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($psychologicalAssignments as $assignment)
                <div class="flex items-start justify-between gap-4 px-5 py-4">
                    <div>
                        <p class="font-medium text-gray-900">{{ $assignment->version->test->name }} · {{ $assignment->version->version }}</p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $assignment->available_from?->format('d M Y H:i') ?? 'Waktu mulai mengikuti arahan admin' }}
                            – {{ $assignment->available_until?->format('d M Y H:i') ?? 'tanpa batas akhir' }}
                        </p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $assignment->is_active ? 'bg-success-50 text-success-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $assignment->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-gray-500">Belum ada psikotes yang ditugaskan.</p>
            @endforelse
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="font-semibold text-gray-900">Atur Psikotes Batch</h2>
        <p class="mt-1 text-sm text-gray-500">Pilih instrumen dan jadwal pelaksanaan psikotes.</p>
        @if($publishedPapiVersions->isEmpty())
            <div class="mt-5 rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700">
                Instrumen PAPI belum siap digunakan. Periksa kelengkapan pada
                <a href="{{ route('admin.recruitment.psychotests.index') }}" class="font-semibold underline">Master Psikotes</a>.
            </div>
        @else
            <form method="POST" action="{{ route('admin.recruitment.psychotests.assign', $batch) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="psychological_test_version_id" class="mb-1.5 block text-sm font-medium text-gray-700">Versi PAPI <span class="text-error-500">*</span></label>
                    <select id="psychological_test_version_id" name="psychological_test_version_id" required class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-500/10">
                        <option value="">Pilih instrumen</option>
                        @foreach($publishedPapiVersions as $version)
                            <option value="{{ $version->id }}" @selected(old('psychological_test_version_id') == $version->id)>{{ $version->test->name }} · {{ $version->version }}</option>
                        @endforeach
                    </select>
                    @error('psychological_test_version_id')<p class="mt-1 text-sm text-error-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="available_from" class="mb-1.5 block text-sm font-medium text-gray-700">Mulai tersedia</label><input id="available_from" type="datetime-local" name="available_from" value="{{ old('available_from') }}" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
                    <div><label for="available_until" class="mb-1.5 block text-sm font-medium text-gray-700">Selesai tersedia</label><input id="available_until" type="datetime-local" name="available_until" value="{{ old('available_until') }}" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
                </div>
                <div>
                    <label for="duration_minutes" class="mb-1.5 block text-sm font-medium text-gray-700">Durasi (menit)</label>
                    <input id="duration_minutes" type="number" min="1" max="1440" name="duration_minutes" value="{{ old('duration_minutes') }}" placeholder="Masukkan durasi pelaksanaan" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    @error('duration_minutes')<p class="mt-1 text-sm text-error-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-3 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="h-4 w-4 rounded border-gray-300 text-brand-600">Aktifkan untuk batch ini</label>
                <button class="crud-btn-primary" type="submit">Simpan Penugasan</button>
            </form>
        @endif
    </div>
</div>
<div class="rounded-2xl border border-gray-200 bg-white">
    <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-semibold text-gray-900">Peserta Batch</h2><p class="mt-1 text-sm text-gray-500">Peserta diurutkan berdasarkan nomor peserta.</p></div><div class="flex flex-wrap gap-2"><a href="{{ route('admin.recruitment.import.template') }}" class="crud-btn-secondary">Unduh Template</a><a href="{{ route('admin.recruitment.import.create', $batch) }}" class="crud-btn-primary">Import Peserta</a></div></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>@foreach(['Nomor Peserta','Nama','Email','Status'] as $heading)<th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">@forelse($participants as $participant)<tr><td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $participant->participant_number }}</td><td class="px-5 py-4 text-sm text-gray-700">{{ $participant->user->name }}</td><td class="px-5 py-4 text-sm text-gray-500">{{ $participant->user->email }}</td><td class="px-5 py-4"><span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Terdaftar</span></td></tr>@empty<tr><td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada peserta. Unduh template lalu lakukan import Excel.</td></tr>@endforelse</tbody></table></div>
    @if($participants->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $participants->links() }}</div>@endif
</div>
@endsection
