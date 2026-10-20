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

## Akses peserta dan pembagian arsip

Pengarsipan memindahkan program dari daftar utama tanpa menghentikan assessment aktif. Admin wajib mengonfirmasi pengarsipan; akses peserta tetap mengikuti jadwal dan status program.

- Peserta login melalui halaman login dengan **NIP + kode akses pribadi**, tanpa tautan undangan atau OTP email. Login staf tetap memakai email/password.
- Kode dibuat otomatis ketika peserta program disimpan, satu kode per peserta/program. Simpan ulang tidak menggantinya. Program lama dapat memakai tombol **Buat Kode** pada **Akses Peserta**.
- NIP harus diisi dan unik. Admin membagikan kode secara pribadi setelah memeriksa identitas. Kode berlaku sekali login; login ulang setelah logout/perangkat kehilangan sesi memerlukan **Ganti Kode** dari Admin. Jawaban dan timer tidak direset.
- Kode hanya dapat digunakan saat kepesertaan aktif dan waktu berada di rentang `starts_at <= sekarang < ends_at`. Jadwal belum lengkap, program nonaktif, dan semua simulasi sudah dikumpulkan menolak login baru.
- Pemeriksaan jadwal, kepesertaan, pencabutan, dan versi kode berlaku pada setiap request. Sesi yang sudah aktif dapat menampilkan presentasi sampai batas program; akses PA saat LGD tetap mengikuti sesi LGD aktif.
- Kode disimpan terenkripsi dengan APP_KEY agar dapat ditampilkan kepada Admin. Hash digunakan untuk pencarian; kode tidak ditaruh di URL, audit log, JSON model, atau flash input. Jaga APP_KEY dan backup-nya; jangan merekam body login di proxy/APM.
- Pada **Arsip Program → Hasil**, Admin menentukan satu asesor untuk setiap kombinasi peserta dan simulasi yang telah dikumpulkan. Mengganti/mencabut pembagian mengubah akses langsung. Penugasan operasional tidak otomatis memberi hak membaca/unduh jawaban.
- Tabel/data undangan dan penilaian lama dipertahankan. Endpoint, service, template, dan scheduler email undangan lama sudah dilepas. Job lama hanya membatalkan pengiriman yang masih antre.

### Setelah deployment

Backup database dan APP_KEY, lalu jalankan migrasi sebelum membuka akses baru:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
npm ci
npm run build
```

Jangan gunakan `migrate:fresh`. Scheduler tetap diperlukan untuk lifecycle program. Restart worker/scheduler persisten setelah deployment agar tidak menjalankan kode lama. SMTP tidak diperlukan untuk login baru. Sesi peserta lama akan diminta login ulang; terbitkan kode untuk program berjalan dan lengkapi pembagian arsip sebelum digunakan. Uji pergantian ini di staging saat tidak ada assessment berlangsung.

Tes otomatis memakai SQLite memory, tanpa mengirim email nyata atau mengubah database aplikasi.

## License Notice

Antarmuka aplikasi dikembangkan dari TailAdmin Laravel. Ketentuan lisensi sumber awal tetap tersedia pada file `LICENSE`.
