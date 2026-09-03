# Handoff Sistem Recruitment dan Psikotes PAPI Kostick

Dokumen ini menyimpan rancangan dan status implementasi modul Recruitment sebelum dipisahkan dari aplikasi Assessment. Salinan kode lengkap terakhir tersedia pada branch `backup/full-system-before-split` di commit `ee6db1d`.

## 1. Ruang lingkup yang sudah dibangun

- Batch rekrutmen dengan status `draft`, `active`, `completed`, dan `cancelled`.
- Import peserta dari spreadsheet, termasuk preview dan validasi sebelum penyimpanan.
- Akun peserta dengan role `peserta_rekrutmen`.
- Master psikotes PAPI Kostick yang berlaku global untuk seluruh batch.
- Satu versi tes terdiri dari 90 pasangan pernyataan, masing-masing memiliki pilihan A dan B.
- Scoring key statis per nomor dan pilihan, bukan pemetaan nomor langsung ke dimensi.
- Penugasan versi tes ke batch, periode akses, durasi, jumlah percobaan, serta status aktif.
- Alur peserta: daftar ujian, instruksi, mulai, simpan jawaban, navigasi maju/mundur, timer berbasis server, dan submit.
- Hasil otomatis untuk 20 dimensi PAPI, daftar hasil per batch, pencarian peserta, dan diagram profil.

## 2. Aturan domain utama

- Seluruh 90 item wajib dijawab.
- Setiap item hanya menerima satu pilihan: `A` atau `B`.
- Jawaban mentah, scoring key, agregat hasil, dan validasi hasil disimpan terpisah.
- Dimensi Role: `G, L, I, T, V, S, R, D, C, E`.
- Dimensi Need: `N, A, P, X, B, O, Z, K, F, W`.
- Konfigurasi dinyatakan valid bila setiap pilihan pada 90 item memiliki scoring rule dan seluruh kode dimensi dikenal.
- Hasil akhir memvalidasi total Role = 45 dan total Need = 45. Hasil yang tidak memenuhi invariant diberi status invalid beserta alasannya.
- Timer menggunakan `started_at` dan `expires_at` di server sehingga refresh, logout, atau perangkat mati tidak mereset waktu.
- Master soal dan scoring key dibuat statis/global; batch hanya memilih versi yang dipublikasikan.

Catatan penting: konten soal dan scoring key merupakan data asesmen sensitif. Jangan menaruhnya pada dokumentasi publik, log, atau respons API peserta. Batasi akses file sumber dan hasil hanya untuk personel berwenang.

## 3. Struktur database

### Recruitment

- `recruitment_batches`: identitas batch, deskripsi, periode, status, pembuat.
- `recruitment_participants`: akun pengguna dan nomor peserta.
- `recruitment_batch_participants`: relasi peserta-batch, status, waktu penugasan.

### Master dan pelaksanaan psikotes

- `psychological_tests`: jenis tes, misalnya PAPI Kostick.
- `psychological_test_versions`: versi master, jumlah item, durasi, invariant total, status validasi/publikasi.
- `papi_dimensions`: 20 dimensi, kategori Role/Need, nama, urutan tampilan.
- `psychological_questions`: nomor 1–90 per versi.
- `psychological_question_options`: pasangan A/B beserta pernyataan.
- `papi_scoring_rules`: pemetaan setiap opsi ke satu dimensi dan bobot.
- `recruitment_batch_psychological_tests`: assignment versi tes ke batch beserta periode dan durasi.
- `psychological_test_sessions`: attempt peserta, status, waktu mulai/kedaluwarsa/submit, posisi terakhir.
- `psychological_answers`: satu opsi terpilih per pertanyaan dan sesi.
- `psychological_results`: status scoring, total Role/Need, versi scoring, alasan invalid.
- `psychological_result_scores`: skor tiap dimensi pada suatu hasil.

Migration asal pada branch backup:

- `database/migrations/2026_08_31_000000_create_recruitment_batch_tables.php`
- `database/migrations/2026_08_31_010000_create_psychological_test_tables.php`

## 4. Lokasi implementasi pada branch backup

- Controller admin: `app/Http/Controllers/Admin/Recruitment*` dan `app/Http/Controllers/Admin/Papi*`.
- Controller peserta: `app/Http/Controllers/Participant/RecruitmentExamController.php`.
- Request validation: `app/Http/Requests/Admin/*Recruitment*`, `app/Http/Requests/Admin/*Papi*`, dan `app/Http/Requests/Participant/StorePapiAnswerRequest.php`.
- Model: `app/Models/Recruitment*`, `app/Models/Psychological*`, serta `app/Models/Papi*`.
- Service: `app/Services/Recruitment` dan `app/Services/PsychologicalTests`.
- View admin: `resources/views/pages/admin/recruitment`.
- View peserta: `resources/views/pages/participant-recruitment`.
- Data master terenkripsi/encoded untuk seeding: `database/data/papi_questions.base64` dan `database/data/papi_scoring_key.base64`.
- Seeder: `PapiDimensionSeeder` dan `PapiStaticMasterSeeder`.
- Tests: `PapiConfigurationPersistenceTest`, `PapiQuestionManagementTest`, `RecruitmentBatchManagementTest`, `RecruitmentParticipantExamFlowTest`, dan `PapiScoringServiceTest`.

## 5. Endpoint yang telah tersedia pada branch backup

Admin menggunakan prefix `/admin`:

- Resource CRUD `/recruitment`.
- Import peserta dan download template pada `/recruitment/{batch}/participants/import`.
- Master psikotes, versi, validasi, dan publikasi pada `/recruitment/psychotests`.
- Import soal dan scoring key pada versi tes.
- Penugasan psikotes ke batch.
- Hasil per batch dan detail hasil pada `/recruitment-results`.

Peserta menggunakan prefix `/peserta-rekrutmen`:

- Daftar ujian.
- Halaman instruksi.
- Mulai sesi.
- Halaman pengerjaan.
- Autosave jawaban dengan throttle.
- Submit final.

## 6. Dependensi khusus

`phpoffice/phpspreadsheet` digunakan untuk import dan ekspor template spreadsheet. Saat membuat repository Recruitment terpisah, salin constraint paket dari branch backup dan jalankan instalasi Composer.

## 7. Langkah memulai repository Recruitment

1. Buat proyek Laravel terpisah atau branch baru dari `backup/full-system-before-split`.
2. Pertahankan fondasi auth, layout, notifikasi, role admin/super admin, dan komponen UI yang dibutuhkan.
3. Salin komponen Recruitment yang tercantum pada bagian 4.
4. Pindahkan migration Recruitment/PAPI ke database baru dan jalankan pada lingkungan kosong.
5. Jalankan seeder dimensi, lalu master statis PAPI.
6. Pastikan master tepat 90 soal, setiap soal memiliki A/B, dan seluruh 180 opsi mempunyai scoring key.
7. Jalankan test khusus Recruitment/PAPI sebelum melakukan perubahan fitur.
8. Tambahkan keputusan client terkait durasi final, format identitas peserta, kredensial/email undangan, kebijakan attempt, dan otorisasi hasil psikolog.

## 8. Pekerjaan yang belum final

- Durasi tes resmi dari client.
- Instruksi pengerjaan final yang disahkan psikolog/client.
- Mekanisme pembuatan atau aktivasi akun untuk sekitar 250 peserta per batch.
- Template identitas peserta final dan mekanisme pengiriman kredensial.
- Interpretasi psikologis/narasi hasil; sistem saat ini berfokus pada skor dimensi dan visual profil.
- Kebijakan retest, pembatalan sesi, dan koreksi data setelah submit.
- Hak akses khusus psikolog untuk melihat/mengesahkan hasil.
- Audit log lengkap untuk akses soal, perubahan scoring key, pengerjaan, dan hasil.
- Kebijakan privasi, retensi, ekspor, dan penghapusan data kandidat.
- Load test dan hardening production untuk pelaksanaan serentak sekitar 250 peserta.

## 9. Checklist keamanan minimum

- Jangan pernah mengirim scoring key ke browser peserta.
- Simpan jawaban melalui ID opsi yang telah diverifikasi milik soal dan versi sesi aktif.
- Gunakan transaksi dan locking saat final submit/scoring agar submit ganda tidak membuat hasil berbeda.
- Batasi akses hasil ke admin/psikolog yang berwenang.
- Jangan menyimpan password polos pada spreadsheet atau email; gunakan aktivasi akun atau tautan reset password sekali pakai.
- Terapkan rate limit, audit trail, backup terenkripsi, HTTPS, dan kebijakan retensi data.
- Lakukan review legal/lisensi atas materi PAPI dan review profesional oleh psikolog sebelum penggunaan operasional.

## 10. Catatan pemisahan

Branch Assessment tidak menjalankan migration penghapusan tabel Recruitment agar data pada database lokal atau production tidak terhapus otomatis. Setelah repository Recruitment baru terverifikasi dan backup database tersedia, pembersihan tabel lama harus dilakukan sebagai pekerjaan terpisah dengan persetujuan eksplisit.
