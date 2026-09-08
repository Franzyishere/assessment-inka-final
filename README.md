# INKA Assessment Management System

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

## License Notice

Antarmuka aplikasi dikembangkan dari TailAdmin Laravel. Ketentuan lisensi sumber awal tetap tersedia pada file `LICENSE`.
