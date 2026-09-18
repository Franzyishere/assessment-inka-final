# INKA Assessment Portal

Aplikasi internal untuk mengelola proses assessment PT Industri Kereta Api (Persero), mulai dari program assessment, bank simulasi, peserta, penugasan asesor, pelaksanaan simulasi, monitoring, penilaian, hingga hasil assessment.

## Technology

- PHP 8.2+
- Laravel 12
- PostgreSQL
- Blade dan Alpine.js
- Tailwind CSS 4
- Vite

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Sesuaikan koneksi database dan konfigurasi aplikasi pada file `.env` sebelum menjalankan migrasi.

## Development

```bash
composer run dev
```

## Testing

```bash
php artisan test
```

## Undangan dan OTP peserta

Peserta tidak lagi masuk dengan password. Admin/Super Admin membuat data peserta (email pribadi diperbolehkan), menambahkan peserta ke program, lalu membuka **Undangan → program → pilih peserta → Kirim**. Asesor dan staf tetap menggunakan login password. Data hasil tetap disimpan untuk staf; peserta hanya mengakses Simulasi Saya pada program yang diundang.

- Tautan berlaku pada tanggal `starts_at` program, pukul 00.00 hingga sebelum 00.00 hari berikutnya, WIB. Ini bukan 24 jam sejak pengiriman. Program lintas hari memerlukan kebijakan tambahan; undangan saat ini hanya berlaku pada hari pertama.
- Undangan boleh dikirim sebelumnya; permintaan OTP baru tersedia pada hari pelaksanaan. Jadwal simulasi sendiri tetap membatasi waktu mulai/pengerjaan.
- OTP berlaku maksimal 10 menit, sekali pakai, 5 percobaan, jeda kirim ulang 60 detik. Kode terikat browser yang meminta. Kode disimpan sebagai hash; kode tidak masuk audit/log. OTP hanya dikirim ke email yang cocok dengan undangan.
- Menerbitkan ulang membatalkan tautan, OTP, dan sesi login sebelumnya. Mencabut undangan, mengubah tanggal program/email peserta, atau menonaktifkan penugasan juga menutup akses. Pemeriksaan berlaku pada setiap request. Login ulang tidak mereset jawaban atau timer simulasi.
- Satu sesi browser hanya mengakses satu undangan/program. Keluar dulu sebelum membuka undangan program lain.

### Aktivasi pengiriman email

Konfigurasikan `.env` (jangan commit kredensial): `ASSESSMENT_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `APP_URL`. Gunakan konfigurasi TLS/port sesuai penyedia SMTP pihak ketiga. Alamat pengirim harus diverifikasi pada penyedia; tidak harus `@inka.co.id`. Domain penerima tidak dibatasi. Transport log/array/failover sengaja ditolak untuk undangan/OTP agar kode tidak bocor ke log atau dianggap terkirim padahal tidak terkirim.

Sebelum produksi: persetujuan IT untuk penyedia pengirim, verifikasi domain SPF/DKIM/DMARC, tes ke email pribadi lalu mailbox INKA, HTTPS, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan redaksi path `/assessment/invitations/*` serta body OTP pada access log/APM/reverse proxy. Jangan log isi email atau OTP. Queue payload undangan dienkripsi memakai APP_KEY; simpan APP_KEY dengan aman.

```bash
php artisan migrate
php artisan config:cache
php artisan queue:restart
php artisan queue:work --tries=1 --timeout=45
```

Gunakan `QUEUE_CONNECTION=database` dan worker yang diawasi Supervisor/systemd untuk undangan massal. OTP dikirim langsung dengan batas waktu SMTP agar tidak menunggu antrean. Monitoring membedakan **Dalam antrean**, **Diterima layanan email**, **Gagal dikirim**, dan **Dibatalkan**; diterima layanan bukan bukti tiba di inbox. Webhook delivery/bounce belum diimplementasikan. Jika gagal, perbaiki konfigurasi lalu kirim ulang. Jika worker berhenti, undangan tetap dalam antrean.

Pengujian otomatis memakai SQLite memory dan mail fake; tidak mengirim email nyata. Perubahan ini memerlukan migrasi sebelum login peserta atau monitoring undangan dipakai. Tidak perlu `migrate:fresh` atau menghapus data lama.

## License Notice

Antarmuka aplikasi dikembangkan dari TailAdmin Laravel. Ketentuan lisensi sumber awal tetap tersedia pada file `LICENSE`.
