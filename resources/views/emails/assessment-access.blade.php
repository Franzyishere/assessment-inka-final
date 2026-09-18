<!doctype html>
<html lang="id"><body style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6">
<h2>INKA Assessment Portal</h2>
<p>Program: <strong>{{ $programName }}</strong></p>
<p>Hari akses: {{ $accessDate }} (WIB).</p>
@if($otp)
    <p>Kode OTP untuk masuk:</p>
    <p style="font-size:28px;font-weight:bold;letter-spacing:6px">{{ $otp }}</p>
    <p>Berlaku maksimal 10 menit, satu kali penggunaan, dan tidak melewati hari akses. Jangan bagikan kode ini kepada siapa pun.</p>
    <p>Jika Anda tidak meminta kode ini, abaikan email ini.</p>
@else
    <p>Anda diundang untuk mengikuti assessment. Pada hari pelaksanaan, buka tautan berikut dan masukkan alamat email penerima undangan untuk mendapatkan OTP.</p>
    <p><a href="{{ $invitationUrl }}">Buka undangan assessment</a></p>
    <p>Tautan hanya dapat digunakan pada hari akses di atas. Tautan yang diterbitkan ulang menggantikan tautan sebelumnya.</p>
@endif
<p>Assess. Develop. Grow.</p>
</body></html>
