@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Undangan Assessment" />
<div class="rounded-2xl border border-gray-200 bg-white" x-data="{ selected: [], confirmSend: false, revoke: null, busy: false, eligible: @js($participants->where('status', 'assigned')->pluck('id')->values()) }">
    <div class="space-y-3 border-b border-gray-200 p-5">
        <a href="{{ route('admin.invitations.index') }}" class="text-sm text-gray-600 underline">Kembali ke program</a>
        <h2 class="text-lg font-semibold text-gray-900">{{ $program->name }}</h2>
        <p class="text-sm text-gray-600">Undangan berlaku pada {{ $program->starts_at?->format('d M Y') ?? 'tanggal yang belum ditetapkan' }}, pukul 00.00–23.59 WIB. OTP berlaku 10 menit.</p>
        <p class="text-xs text-gray-500">Status “Diterima layanan email” bukan konfirmasi email masuk inbox. Pengiriman nyata membutuhkan konfigurasi email dan worker antrean yang aktif.</p>
        <x-common.search-form :action="route('admin.invitations.show', $program)" placeholder="Cari nama atau email peserta…" />
        <button type="button" class="crud-btn-primary" :disabled="!selected.length" @click="confirmSend = true">Kirim / Kirim Ulang Terpilih (<span x-text="selected.length">0</span>)</button>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-700"><tr>
            <th class="p-4"><input type="checkbox" aria-label="Pilih semua peserta di halaman ini" @change="selected = $event.target.checked ? [...eligible] : []" :checked="eligible.length > 0 && selected.length === eligible.length"></th>
            @foreach(['Peserta', 'Akses undangan', 'Email undangan', 'OTP terakhir', 'Login terakhir', 'Aksi'] as $heading)<th class="p-4">{{ $heading }}</th>@endforeach
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($participants as $participant)
            @php($invitation = $participant->invitation)
            <tr>
                <td class="p-4"><input type="checkbox" value="{{ $participant->id }}" x-model.number="selected" @disabled($participant->status !== 'assigned') aria-label="Pilih {{ $participant->user->name }}"></td>
                <td class="p-4"><span class="font-semibold text-gray-900">{{ $participant->user->name }}</span><br><span class="text-gray-600">{{ $participant->user->email }}</span></td>
                <td class="p-4">{{ $invitation?->accessLabel() ?? 'Belum diundang' }}</td>
                <td class="p-4">{{ $invitation?->latestInvitationDelivery?->statusLabel() ?? '—' }}<br><span class="text-xs text-gray-500">{{ $invitation?->latestInvitationDelivery?->sent_at?->format('d M Y H:i') }}</span></td>
                <td class="p-4">{{ $invitation?->latestOtpDelivery?->statusLabel() ?? '—' }}</td>
                <td class="p-4">{{ $invitation?->last_login_at?->format('d M Y H:i') ?? '—' }}</td>
                <td class="p-4"><div class="crud-actions">@if($invitation && !$invitation->revoked_at)<button type="button" class="crud-btn-soft-danger" @click="revoke = @js(route('admin.invitations.revoke', [$program, $invitation]))">Cabut Akses</button>@endif</div></td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-8 text-center text-gray-500">Tidak ada peserta. Tambahkan peserta melalui pengaturan program.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="p-5">{{ $participants->links() }}</div>
    <div x-show="confirmSend || revoke" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="invitation-confirm-title" @keydown.escape.window="if (!busy) { confirmSend = false; revoke = null; }">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-lg">
            <h3 id="invitation-confirm-title" class="text-lg font-semibold text-gray-900" x-text="revoke ? 'Cabut akses undangan?' : 'Kirim undangan assessment?'"></h3>
            <p class="mt-3 text-sm text-gray-600">Tautan dan sesi login sebelumnya akan dibatalkan. Jawaban yang sudah tersimpan tidak dihapus. Peserta perlu masuk lagi dengan undangan yang berlaku.</p>
            <form method="POST" :action="revoke || @js(route('admin.invitations.send', $program))" @submit="busy = true" class="mt-5 flex justify-end gap-2">
                @csrf
                <template x-if="revoke"><input type="hidden" name="_method" value="DELETE"></template>
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <button type="button" :disabled="busy" class="crud-btn-soft-neutral" @click="confirmSend = false; revoke = null">Batal</button>
                <button type="submit" :disabled="busy" class="crud-btn-primary" x-text="busy ? 'Memproses…' : 'Konfirmasi'">Konfirmasi</button>
            </form>
        </div>
    </div>
</div>
@endsection
