@php
    $logoPath = public_path('images/logo/logo-inka-sidebar.png');
    $hasLogo = file_exists($logoPath);
    $logoUrl = ($hasLogo && isset($message) && is_object($message) && method_exists($message, 'embed'))
        ? $message->embed($logoPath)
        : ($hasLogo ? asset('images/logo/logo-inka-sidebar.png') : null);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $otp ? 'Kode OTP Akses Assessment' : 'Undangan Assessment PT INKA (Persero)' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;line-height:1.6;-webkit-font-smoothing:antialiased;">
    <!-- Container Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f5f7;padding:30px 15px;">
        <tr>
            <td align="center">
                <!-- Main Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);border:1px solid #e5e7eb;">
                    
                    <!-- Red Brand Accent Bar -->
                    <tr>
                        <td style="height:6px;background:linear-gradient(90deg, #e21a29, #c91422);font-size:0;line-height:0;">&nbsp;</td>
                    </tr>

                    <!-- Header -->
                    <tr>
                        <td style="padding:28px 36px 20px;text-align:center;background-color:#ffffff;border-bottom:1px solid #f3f4f6;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        @if($logoUrl)
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 12px;">
                                                <tr>
                                                    <td align="center">
                                                        <img src="{{ $logoUrl }}" alt="PT Industri Kereta Api (Persero)" width="140" style="display:block;width:140px;max-width:140px;height:auto;border:0;outline:none;text-decoration:none;">
                                                    </td>
                                                </tr>
                                            </table>
                                        @else
                                            <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:1.5px;color:#e21a29;text-transform:uppercase;">PT Industri Kereta Api (Persero)</p>
                                        @endif
                                        <h1 style="margin:0;font-size:20px;font-weight:800;color:#111827;letter-spacing:-0.5px;">INKA Assessment Portal</h1>
                                        <p style="margin:4px 0 0;font-size:12px;color:#6b7280;font-weight:500;">Assess. Develop. Grow.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:32px 36px;">
                            <p style="margin:0 0 16px;font-size:15px;color:#374151;">Halo Rekan Peserta,</p>

                            @if($otp)
                                <!-- OTP MODE -->
                                <p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
                                    Berikut adalah kode verifikasi <strong>One-Time Password (OTP)</strong> Anda untuk masuk ke sistem assessment program <strong>{{ $programName }}</strong>:
                                </p>

                                <!-- OTP Box -->
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                                    <tr>
                                        <td align="center">
                                            <div style="background-color:#fff1f2;border:2px dashed #f96876;border-radius:14px;padding:22px 20px;text-align:center;">
                                                <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#991b1b;">Kode OTP Anda</p>
                                                <div style="font-family:'Courier New',Courier,monospace;font-size:30px;font-weight:800;letter-spacing:6px;color:#c91422;margin-left:6px;word-break:break-all;">
                                                    {{ $otp }}
                                                </div>
                                                <p style="margin:10px 0 0;font-size:12px;color:#b91c1c;font-weight:600;">
                                                    ⏱ Berlaku 10 menit
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:16px 0 24px;font-size:13px;color:#6b7280;line-height:1.6;background-color:#fef2f2;padding:12px 16px;border-radius:8px;border-left:3px solid #ef4444;">
                                    🔒 <strong>Keamanan:</strong> Jangan berikan kode OTP ini kepada siapa pun. Kode hanya berlaku untuk sesi browser yang sedang Anda gunakan.
                                </p>

                            @else
                                <!-- INVITATION MODE -->
                                <p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
                                    Anda telah dijadwalkan untuk mengikuti kegiatan simulasi assessment secara daring. Berikut adalah rincian pelaksanaan Anda:
                                </p>

                                <!-- Details Table -->
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;margin:0 0 26px;overflow:hidden;">
                                    <tr>
                                        <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#6b7280;width:35%;">Program</td>
                                        <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#111827;">{{ $programName }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#6b7280;">Hari Akses</td>
                                        <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:600;color:#111827;">{{ $accessDate }} (WIB)</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 18px;font-size:13px;color:#6b7280;">Batas Sesi</td>
                                        <td style="padding:14px 18px;font-size:14px;font-weight:600;color:#e21a29;">Maksimal s.d. Pukul 17.00 WIB</td>
                                    </tr>
                                </table>

                                <!-- CTA Button -->
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $invitationUrl }}" target="_blank" style="display:inline-block;background-color:#e21a29;color:#ffffff;font-size:15px;font-weight:700;line-height:1;padding:16px 36px;border-radius:10px;text-decoration:none;box-shadow:0 4px 12px rgba(226,26,41,0.25);">
                                                Buka Portal Assessment &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:16px 0 0;font-size:12px;color:#6b7280;text-align:center;">
                                    Atau salin tautan berikut ke peramban Anda:<br>
                                    <a href="{{ $invitationUrl }}" target="_blank" style="color:#e21a29;word-break:break-all;text-decoration:underline;">{{ $invitationUrl }}</a>
                                </p>
                            @endif

                            <!-- Tips Section -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;">
                                <tr>
                                    <td>
                                        <p style="margin:0 0 8px;font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.5px;">💡 Petunjuk Pelaksanaan:</p>
                                        <ul style="margin:0;padding-left:18px;font-size:12px;color:#475569;line-height:1.7;">
                                            <li>Gunakan laptop atau komputer dengan peramban <strong>Google Chrome</strong> terbaru.</li>
                                            <li>Pastikan koneksi internet stabil dan perangkat terhubung ke sumber daya listrik.</li>
                                            <li>Sistem mewajibkan <strong>mode layar penuh (fullscreen)</strong> selama simulasi berjalan.</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 36px 28px;background-color:#fafafa;border-top:1px solid #f3f4f6;text-align:center;">
                            <p style="margin:0 0 6px;font-size:12px;font-weight:700;color:#374151;">Divisi Human Capital &amp; General Affair (HCGA)</p>
                            <p style="margin:0 0 10px;font-size:11px;color:#6b7280;line-height:1.4;">
                                PT Industri Kereta Api (Persero)<br>
                                Jl. Yos Sudarso No. 71, Madiun, Jawa Timur, 63122
                            </p>
                            <p style="margin:10px 0 0;font-size:10px;color:#9ca3af;border-top:1px dashed #e5e7eb;padding-top:10px;">
                                Email ini dibuat secara otomatis oleh INKA Assessment Portal. Harap tidak membalas email ini.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
