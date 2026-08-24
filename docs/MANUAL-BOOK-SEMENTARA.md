# MANUAL BOOK SEMENTARA

## Sistem Assessment PT Industri Kereta Api (Persero)

**Versi dokumen:** 0.2 (Draft)  
**Status aplikasi:** Pengembangan / prototipe fungsional  
**Tanggal dokumen:** 21 Agustus 2026  
**Zona waktu sistem:** WIB (Asia/Jakarta)

---

## 1. Pendahuluan

Sistem Assessment INKA merupakan aplikasi berbasis web yang dirancang untuk membantu pengelolaan kegiatan assessment secara terpusat. Sistem memisahkan akses pengguna berdasarkan peran, mulai dari pengelolaan akun, penyusunan bank simulasi, pembentukan program assessment, penugasan asesor, pelaksanaan simulasi oleh peserta, monitoring, hingga penilaian dan rekomendasi.

Dokumen ini merupakan manual book sementara berdasarkan fitur yang telah tersedia pada versi pengembangan saat ini. Beberapa modul masih dalam tahap penyempurnaan dan dijelaskan pada bagian status pengembangan.

## 2. Tujuan Sistem

Sistem ini dikembangkan untuk:

1. Memusatkan data pengguna dan peserta assessment.
2. Menyediakan Bank Simulasi yang dapat digunakan kembali.
3. Mengelompokkan peserta, simulasi, jadwal, dan asesor dalam Program Assessment.
4. Mendukung pelaksanaan empat jenis simulasi assessment.
5. Memudahkan asesor memonitor peserta dan memberikan penilaian.
6. Mencatat aktivitas penting untuk kebutuhan kontrol dan audit.

## 3. Ruang Lingkup Versi Saat Ini

### 3.1 Fitur yang telah tersedia

- Login menggunakan email dan password.
- Pembatasan menu dan halaman berdasarkan role.
- Manajemen pengguna oleh Super Admin.
- Informasi role dan hak akses.
- Audit log untuk aktivitas tertentu.
- Pengelolaan akun Peserta Assessment oleh Admin HCGA.
- Pengelolaan Bank Simulasi.
- Pengelolaan Program Assessment.
- Pemilihan simulasi, peserta, dan asesor dalam program.
- Penugasan satu atau beberapa asesor sebagai Tim Asesor Program untuk seluruh simulasi.
- Monitoring progres assessment oleh Admin HCGA.
- Daftar simulasi dan peserta yang ditugaskan kepada asesor.
- Monitoring simulasi pada sisi asesor.
- Pelaksanaan simulasi oleh peserta.
- Preview materi PDF berukuran besar dan satu kolom jawaban pada Simulasi 1.
- Review materi dan jawaban Simulasi 1 secara read-only pada Simulasi 2.
- Paket materi PDF dan kolom jawaban per materi pada Simulasi 3.
- Pemilahan Critical Incident atau In-Tray berdasarkan kategori peserta.
- Upload dan preview fullscreen presentasi PDF pada Simulasi 4.
- Penghapusan data administratif dengan validasi riwayat penggunaan.
- Pencatatan aktivitas selama pengerjaan.
- Penilaian asesor dalam bentuk draft atau final.
- Rekomendasi hasil penilaian.

### 3.2 Fitur dalam pengembangan

- Halaman jadwal khusus Peserta Assessment.
- Publikasi hasil dan rekomendasi kepada peserta.
- Rekapitulasi hasil dari beberapa asesor.
- Rubrik kompetensi dan skor terstruktur.
- Penguatan penguncian perangkat dan sesi anti-kecurangan.
- Auto-save dan auto-submit saat waktu habis.
- Modul Rekrutmen, tahapan seleksi, ujian, dan hasil rekrutmen.

## 4. Peran Pengguna

| Role | Tanggung Jawab Utama |
|---|---|
| Super Admin | Mengelola seluruh akun, role, hak akses, dan audit log. |
| Admin HCGA | Mengelola peserta, Bank Simulasi, Program Assessment, penugasan asesor, dan monitoring. |
| Asesor | Melihat simulasi dan peserta yang ditugaskan, memonitor pengerjaan, serta memberikan penilaian dan rekomendasi. |
| Peserta Assessment | Melihat dan mengerjakan simulasi yang ditugaskan dalam Program Assessment. |
| Peserta Rekrutmen | Role telah tersedia, tetapi alur operasional rekrutmen masih dalam pengembangan. |

## 5. Akses Sistem

### 5.1 Login

1. Buka alamat aplikasi melalui browser.
2. Masukkan email yang telah didaftarkan.
3. Masukkan password akun.
4. Tekan tombol **Masuk**.
5. Sistem mengarahkan pengguna ke dashboard sesuai role.

> Kredensial uji diberikan secara terpisah oleh administrator dan tidak dicantumkan dalam dokumen ini.

### 5.2 Logout

1. Buka menu profil pada bagian kanan atas halaman.
2. Pilih **Keluar**.
3. Sistem menutup sesi dan mengarahkan pengguna kembali ke halaman login.

## 6. Panduan Super Admin

### 6.1 Dashboard Sistem

Dashboard menampilkan ringkasan kondisi pengguna dan fondasi sistem sesuai data yang tersedia.

### 6.2 Manajemen Pengguna

1. Pilih menu **Manajemen Pengguna**.
2. Tekan **Tambah Pengguna** untuk membuat akun.
3. Isi nama, email, role, dan password.
4. Simpan data.
5. Untuk memperbarui akun, tekan **Edit** pada pengguna terkait.

Super Admin dapat mengelola akun dengan role:

- Super Admin.
- Admin HCGA.
- Asesor.
- Peserta Assessment.
- Peserta Rekrutmen.

### 6.3 Role dan Hak Akses

Menu **Role & Hak Akses** menampilkan pembagian kewenangan setiap role. Proteksi tidak hanya diterapkan pada menu, tetapi juga pada route aplikasi sehingga pengguna tidak dapat membuka halaman role lain secara langsung.

### 6.4 Audit Log

Menu **Audit Log** digunakan untuk melihat aktivitas yang sudah dicatat sistem, antara lain login, logout, pembuatan atau perubahan akun, serta aktivitas administratif tertentu.

## 7. Panduan Admin HCGA

### 7.1 Mengelola Peserta Assessment

1. Pilih menu **Peserta Assessment**.
2. Tekan **Tambah Peserta Assessment**.
3. Isi nama, email, dan password awal.
4. Simpan data peserta.
5. Gunakan tombol **Edit** apabila data peserta perlu diperbarui.
6. Gunakan tombol **Hapus** untuk menghapus akun yang belum pernah dimasukkan ke Program Assessment.

Peserta yang telah dimasukkan ke dalam program tidak dapat dihapus agar riwayat assessment tetap terjaga.

### 7.2 Mengelola Bank Simulasi

Bank Simulasi menyediakan empat jenis simulasi tetap. Admin tidak membuat atau menghapus jenis simulasi, tetapi memperbarui materi dan durasinya.

1. Pilih menu **Bank Simulasi**.
2. Pilih **Edit Materi** pada simulasi yang akan diperbarui.
3. Untuk PA, unggah satu PDF yang digunakan seluruh peserta.
4. Untuk Simulasi 3, pilih paket kategori Critical Incident atau In-Tray lalu kelola beberapa PDF materinya.
5. Tentukan durasi jika simulasi menggunakan timer.
6. Tekan **Simpan Materi**.

Kode, judul, dan status aktif dikelola otomatis oleh sistem. LGD tidak memiliki upload materi karena menggunakan materi serta jawaban PA. Presentasi hanya menjadi sarana upload PDF peserta.

#### Jenis simulasi

| Urutan | Simulasi | Mekanisme Saat Ini |
|---|---|---|
| 1 | Problem Analysis (PA) | Admin mengunggah satu PDF; peserta membacanya dalam preview besar dan mengisi satu kolom jawaban. |
| 2 | Leaderless Group Discussion (LGD) | Materi dan jawaban PA ditampilkan kembali secara read-only, menggunakan timer, lalu dikumpulkan oleh peserta. |
| 3 | Critical Incident / In-Tray | Admin mengunggah beberapa PDF; setiap materi memiliki kolom jawaban dan menggunakan satu timer keseluruhan. |
| 4 | Presentasi Peserta | Peserta mengunggah PDF untuk dipreview dan ditampilkan fullscreen oleh asesor. |

Keempat simulasi otomatis dipasang ketika Admin menyimpan pengaturan Program Assessment. Materi dapat diperbarui, sedangkan jenis simulasi utama tidak dapat dihapus.

### 7.3 Membuat Program Assessment

Program Assessment merupakan wadah pelaksanaan yang menghubungkan periode, peserta, simulasi, dan asesor.

1. Pilih menu **Program Assessment**.
2. Tekan tombol untuk membuat program baru.
3. Isi nama program, deskripsi, waktu mulai, waktu selesai, dan status.
4. Simpan program.
5. Buka bagian **Atur Program** untuk menyusun pelaksanaannya.

Status program yang digunakan:

- **Draft:** program masih disiapkan.
- **Aktif:** program dapat digunakan dalam pelaksanaan.
- **Arsip:** program telah disimpan sebagai arsip dan tidak digunakan untuk memulai simulasi baru.

Program hanya dapat dihapus apabila masih berstatus **Draft** dan belum memiliki riwayat pengerjaan peserta.

### 7.4 Menyusun Program dan Menugaskan Asesor

1. Buka Program Assessment yang akan diatur.
2. Pilih peserta yang mengikuti program.
3. Tentukan kategori assessment setiap peserta.
4. Tentukan Tim Asesor Program satu kali untuk seluruh simulasi.
5. Simpan susunan program; seluruh simulasi bawaan dipasang otomatis.

Setelah disimpan, sistem membuat jadwal simulasi berdasarkan waktu mulai dan selesai program. Tim yang dipilih otomatis ditugaskan ke seluruh simulasi dan dapat dikelola kembali melalui menu **Penugasan Asesor**.

Satu program dapat memuat peserta dari seluruh kategori pada hari yang sama. Problem Analysis, LGD, dan Presentasi berlaku untuk seluruh peserta. Pada Simulasi 3, sistem menampilkan salah satu dari enam paket sesuai kategori; khusus Spesialis Madya, peserta memilih CI 3 atau In-Tray 3.

### 7.5 Monitoring Assessment

1. Pilih menu **Monitoring**.
2. Pilih Program Assessment.
3. Sistem menampilkan target sesi, peserta yang sudah mulai, submission yang masuk, penilaian final, dan aktivitas yang tercatat.
4. Tabel detail menampilkan progres setiap peserta pada masing-masing simulasi.

Status sesi yang dapat muncul:

- Belum mulai.
- Sedang dikerjakan.
- Dikumpulkan.
- Kedaluwarsa.

## 8. Panduan Asesor

### 8.1 Simulasi Ditugaskan

1. Pilih menu **Simulasi Ditugaskan**.
2. Sistem hanya menampilkan simulasi yang ditugaskan kepada asesor yang sedang login.
3. Buka salah satu simulasi untuk melihat peserta dan status pengerjaan.

Untuk Simulasi 2, peserta menekan **Simpan & Kumpulkan** setelah LGD selesai. Asesor kemudian melanjutkan ke proses penilaian.

### 8.2 Peserta Assessment

Menu **Peserta Assessment** menampilkan peserta dari program yang berkaitan dengan penugasan asesor. Informasi yang tersedia meliputi status penugasan, jumlah simulasi yang dimulai, dan jumlah simulasi yang sudah dikumpulkan.

### 8.3 Monitoring Simulasi

Menu **Monitoring Simulasi** menampilkan:

- Jumlah peserta yang menjadi tanggung jawab asesor.
- Jumlah peserta yang sudah memulai.
- Jumlah submission yang masuk.
- Jumlah penilaian yang sudah diselesaikan asesor.
- Jumlah aktivitas pengerjaan yang tercatat.
- Persentase peserta yang telah mengumpulkan.

### 8.4 Penilaian dan Rekomendasi

1. Pilih menu **Penilaian & Rekomendasi**.
2. Buka sesi peserta yang sudah dikumpulkan.
3. Periksa jawaban atau file peserta.
4. Isi catatan penilaian.
5. Pilih rekomendasi apabila penilaian akan difinalisasi.
6. Pilih **Simpan Draft** apabila penilaian belum selesai atau **Finalisasi** jika sudah selesai.

Pilihan rekomendasi yang tersedia:

- Direkomendasikan.
- Direkomendasikan dengan pengembangan.
- Tidak direkomendasikan.

Penilaian yang sudah difinalisasi tidak dapat diubah kembali pada versi saat ini.

## 9. Panduan Peserta Assessment

### 9.1 Melihat Simulasi

1. Pilih menu **Simulasi Saya**.
2. Sistem menampilkan Program Assessment dan simulasi yang ditugaskan.
3. Status awal simulasi adalah **Belum dimulai**.
4. Tekan **Lihat Detail** untuk membuka informasi simulasi.

### 9.2 Memulai Simulasi

Sebelum memulai, peserta harus memastikan:

- Koneksi internet stabil.
- Perangkat memiliki daya yang cukup.
- Waktu yang tersedia sesuai durasi simulasi.
- Siap menggunakan mode fullscreen.

Tekan **Mulai Simulasi** untuk membuat sesi dan menjalankan timer. Waktu dihitung berdasarkan durasi simulasi sejak tombol mulai ditekan.

### 9.3 Timer Pengerjaan

Timer ditampilkan secara sticky agar tetap terlihat ketika peserta membaca materi. Indikator warna timer:

- Biru: waktu masih mencukupi.
- Kuning: waktu tersisa 15 menit atau kurang.
- Merah: waktu tersisa 5 menit atau kurang.

Format timer adalah `jam:menit:detik`.

### 9.4 Mengerjakan Simulasi 1

1. Buka preview PDF materi dalam tampilan besar.
2. Baca seluruh uraian Problem Analysis.
3. Isi jawaban pada satu kolom jawaban yang tersedia.
4. Simpan jawaban.
5. Tekan **Kumpulkan Simulasi**.

Sistem menolak pengumpulan jika jawaban wajib belum diisi.

### 9.5 Mengikuti Simulasi 2

1. Buka Simulasi 2 — Leaderless Group Discussion.
2. Sistem menampilkan kembali materi PDF dan jawaban Simulasi 1.
3. Gunakan materi dan jawaban tersebut sebagai bahan LGD.
4. Materi dan jawaban hanya dapat dibaca dan tidak dapat diubah.
5. Perhatikan timer Simulasi 2 selama pelaksanaan LGD.
6. Centang konfirmasi, lalu tekan **Simpan & Kumpulkan** setelah selesai.

Simulasi 2 menggunakan timer sistem. Materi dan jawaban Simulasi 1 tetap read-only, sedangkan laporan LGD dilaksanakan secara offline dan tidak menjadi bagian dari fitur aplikasi.

### 9.6 Mengerjakan Simulasi 3

1. Sistem menampilkan paket sesuai kategori peserta. Khusus Spesialis Madya, peserta memilih **Critical Incident 3** atau **In-Tray 3** sebelum memulai.
2. Peserta Madya memeriksa pilihan, menyetujui konfirmasi final, lalu menekan **Simpan & Kunci Pilihan**. Timer belum berjalan pada tahap ini.
3. Buka dan baca PDF pada setiap materi.
4. Isi jawaban tepat di bawah masing-masing materi.
5. Gunakan waktu dengan memperhatikan satu timer untuk keseluruhan paket.
6. Periksa seluruh jawaban.
7. Tekan **Kumpulkan Respons**.

Pilihan peserta Madya tidak dapat diubah setelah dikonfirmasi. Sistem mengunci akses ke paket lainnya dan mencatat pilihan pada audit log.

Wawancara setelah Critical Incident atau In-Tray dilaksanakan secara offline dan tidak menjadi bagian dari fitur aplikasi.

### 9.7 Mengunggah Presentasi pada Simulasi 4

1. Buka Simulasi 4.
2. Pilih file presentasi.
3. Format yang diterima hanya PDF.
4. Ukuran maksimal file adalah 25 MB.
5. Tekan **Kumpulkan Presentasi**.

File yang telah dikumpulkan tidak dapat diganti pada versi saat ini.

Asesor dapat membuka file tersebut melalui preview besar atau mode fullscreen pada dashboard asesor.

### 9.8 Pencatatan Aktivitas

Selama pengerjaan, sistem mewajibkan penggunaan mode fullscreen. Jika peserta keluar dari fullscreen, halaman pengerjaan dikunci sampai fullscreen diaktifkan kembali. Kejadian keluar atau gagal mengaktifkan fullscreen dicatat untuk mendukung monitoring.

Pencatatan tersebut merupakan informasi pendukung bagi admin dan asesor, bukan keputusan otomatis mengenai hasil assessment.

## 10. Matriks Simulasi Berdasarkan Kategori

Satu Program Assessment dapat diikuti oleh peserta dari beberapa atau seluruh kategori pada hari yang sama.

| Kategori Assessment | PA | LGD | Critical Incident | In-Tray | Presentasi |
|---|:---:|:---:|:---:|:---:|:---:|
| Kenaikan Golongan I ke II | Ya | Ya | Ya | - | Ya |
| Kenaikan Golongan II ke III | Ya | Ya | Ya | - | Ya |
| Kenaikan Golongan III ke IV | Ya | Ya | Ya | - | Ya |
| Promosi SPV | Ya | Ya | Ya | - | Ya |
| Promosi Spesialis Pratama | Ya | Ya | Ya | - | Ya |
| Promosi M | Ya | Ya | - | Ya | Ya |
| Promosi Spesialis Muda | Ya | Ya | Ya | - | Ya |
| Promosi SM | Ya | Ya | - | Ya | Ya |
| Promosi Spesialis Madya | Ya | Ya | Pilih salah satu | Pilih salah satu | Ya |

Laporan LGD dan wawancara setelah Critical Incident atau In-Tray dilaksanakan secara offline. Sistem tidak meminta peserta mengunggah laporan maupun hasil wawancara tersebut.

## 11. Panduan Skenario Demo kepada Client

Skenario berikut mendemonstrasikan satu program yang dilaksanakan pada hari yang sama dan diikuti oleh seluruh kategori peserta.

### 11.1 Data Contoh

- Program: **Assessment Internal INKA Agustus 2026**.
- Pelaksanaan: 25 Agustus 2026, pukul 08.00–17.00 WIB.
- Status awal: **Draft**.

Siapkan minimal satu peserta dari setiap kategori:

| Contoh Peserta | Kategori | Paket Simulasi 3 |
|---|---|---|
| Peserta A | Kenaikan Golongan I ke II | Critical Incident 1 |
| Peserta B | Kenaikan Golongan II ke III | Critical Incident 2 |
| Peserta C | Kenaikan Golongan III ke IV | Critical Incident 3 |
| Peserta D | Promosi SPV | Critical Incident 1 |
| Peserta E | Promosi Spesialis Pratama | Critical Incident 1 |
| Peserta F | Promosi M | In-Tray 1 |
| Peserta G | Promosi Spesialis Muda | Critical Incident 2 |
| Peserta H | Promosi SM | In-Tray 2 |
| Peserta I | Promosi Spesialis Madya | Memilih CI 3 atau In-Tray 3 |

### 11.2 Menyiapkan Akun

1. Login sebagai Super Admin dan pastikan akun Admin HCGA serta asesor tersedia.
2. Login sebagai Admin HCGA.
3. Buka **Peserta Assessment**.
4. Buat akun Peserta A sampai Peserta I.

Kategori assessment belum ditentukan pada akun. Kategori dipilih ketika peserta dimasukkan ke Program Assessment.

### 11.3 Membuat Program Assessment

1. Buka **Program Assessment** dan tekan **Buat Program**.
2. Masukkan nama, deskripsi, serta waktu pelaksanaan sesuai data contoh.
3. Pilih status **Draft**.
4. Simpan program.

Pertahankan status Draft sampai Bank Simulasi, peserta, dan penugasan asesor selesai disiapkan.

### 11.4 Menyiapkan Bank Simulasi

Buka **Bank Simulasi**, kemudian perbarui materi pada katalog yang sudah tersedia:

1. Satu Problem Analysis untuk seluruh peserta, berisi satu PDF dan durasi pengerjaan.
2. Satu Leaderless Group Discussion untuk seluruh peserta.
3. **Critical Incident 1** untuk Golongan I ke II, Spesialis Pratama, dan SPV.
4. **Critical Incident 2** untuk Golongan II ke III dan Spesialis Muda.
5. **Critical Incident 3** untuk Golongan III ke IV serta salah satu pilihan Spesialis Madya.
6. **In-Tray 1** untuk Promosi M.
7. **In-Tray 2** untuk Promosi SM.
8. **In-Tray 3** sebagai salah satu pilihan Spesialis Madya.
9. Satu Presentasi untuk seluruh peserta.

Setiap paket Simulasi 3 dapat memuat beberapa PDF. Setiap PDF mempunyai kolom jawaban, sementara timer berlaku untuk keseluruhan paket.

Secara teknis katalog menyimpan satu PA, satu LGD, enam paket materi Simulasi 3, serta satu Presentasi. Pada antarmuka semuanya tetap dikelompokkan sebagai empat simulasi utama.

### 11.5 Menyusun Program dan Menentukan Kategori Peserta

1. Kembali ke **Program Assessment**.
2. Tekan **Atur** pada program contoh.
3. Pilih Peserta A sampai Peserta I.
4. Tentukan kategori setiap peserta sesuai tabel data contoh.
5. Pilih Tim Asesor Program.
6. Simpan pengaturan; seluruh simulasi dipasang otomatis.

Setiap peserta hanya menjalani empat rangkaian:

- PA, LGD, dan Presentasi untuk seluruh peserta.
- Tiga paket Critical Incident sesuai matriks kategori.
- Tiga paket In-Tray sesuai matriks kategori dan pilihan Madya.

### 11.6 Menugaskan Asesor

Pada halaman pengaturan program atau menu **Penugasan Asesor**:

1. Pilih satu atau beberapa asesor sebagai Tim Asesor Program.
2. Gunakan **Pilih semua asesor** apabila seluruh akun asesor perlu dilibatkan.
3. Simpan pengaturan.
4. Sistem menerapkan tim tersebut secara otomatis ke PA, LGD, seluruh paket Critical Incident/In-Tray, dan Presentasi dalam program.

Setiap anggota tim dapat melihat seluruh simulasi dan peserta dalam program tersebut. Struktur penugasan tetap dicatat pada setiap simulasi untuk menjaga otorisasi dan riwayat penilaian.

### 11.7 Memeriksa dan Mengaktifkan Program

Sebelum aktivasi, pastikan:

- Seluruh peserta sudah memiliki kategori.
- PA, LGD, paket CI/In-Tray, dan Presentasi sudah dipasang.
- Paket Simulasi 3 sesuai dengan seluruh kategori peserta.
- Seluruh simulasi berstatus Aktif.
- Tim Asesor Program sudah ditentukan dan diterapkan ke seluruh simulasi.
- Jadwal program sudah benar.

Buka **Edit Program**, ubah status menjadi **Aktif**, kemudian simpan.

### 11.8 Demo sebagai Peserta

Login sebagai Peserta E — Promosi Spesialis Pratama:

1. Buka **Simulasi Saya**.
2. Mulai PA, baca PDF, isi satu jawaban, lalu kumpulkan.
3. Buka LGD dan tunjukkan materi serta jawaban PA dalam kondisi read-only, timer, dan tombol Simpan & Kumpulkan.
4. Buka Simulasi 3 dan tunjukkan bahwa Peserta E otomatis memperoleh Critical Incident 1.
5. Tunjukkan beberapa PDF, kolom jawaban per materi, dan satu timer keseluruhan.
6. Buka Presentasi dan unggah PDF.

Kemudian login sebagai Peserta F — Promosi M untuk menunjukkan bahwa Simulasi 3 yang tampil adalah In-Tray 1. Login sebagai Peserta I — Spesialis Madya untuk mendemonstrasikan pilihan final antara CI 3 dan In-Tray 3.

### 11.9 Demo sebagai Asesor

1. Login sebagai asesor yang ditugaskan.
2. Buka **Simulasi Ditugaskan** dan tunjukkan batas penugasannya.
3. Periksa peserta, status pengerjaan, dan jawaban yang masuk.
4. Pada LGD, tunjukkan kontrol penyelesaian observasi.
5. Pada Presentasi, buka preview PDF dan mode fullscreen.
6. Buka **Penilaian & Rekomendasi**.
7. Isi catatan dan rekomendasi, kemudian simpan draft atau finalisasi.

### 11.10 Demo Monitoring Admin

1. Login kembali sebagai Admin HCGA.
2. Buka **Monitoring** dan pilih program contoh.
3. Tunjukkan status setiap peserta dan simulasi.
4. Tunjukkan progres submission, aktivitas pengerjaan, serta penilaian asesor.

### 11.11 Ringkasan Demo

```text
Super Admin menyiapkan akun asesor
→ Admin membuat akun peserta
→ Admin membuat Program Assessment berstatus Draft
→ Admin memperbarui materi PA
→ Admin memperbarui tiga paket Critical Incident
→ Admin memperbarui tiga paket In-Tray
→ Sistem memasang seluruh simulasi ke program secara otomatis
→ Admin memilih peserta dan menentukan kategorinya
→ Admin menugaskan asesor
→ Admin memeriksa lalu mengaktifkan program
→ Peserta mengerjakan simulasi sesuai kategori; peserta Madya memilih CI 3 atau In-Tray 3
→ Asesor memonitor dan melakukan penilaian
→ Admin memonitor keseluruhan pelaksanaan
```

## 12. Alur Operasional Assessment

```text
Super Admin membuat akun
→
Admin HCGA menyiapkan Bank Simulasi
→
Admin HCGA membuat Program Assessment
→
Admin memilih peserta, simulasi, dan asesor
→
Peserta membuka dan mengerjakan simulasi
→
Admin dan asesor memonitor progres
→
Asesor memeriksa submission
→
Asesor menyimpan atau memfinalisasi penilaian
```

## 13. Ketentuan dan Catatan Penggunaan

1. Gunakan browser versi terbaru untuk pengalaman terbaik.
2. Jangan membagikan akun kepada pengguna lain.
3. Admin harus memastikan zona waktu dan jadwal program telah sesuai WIB.
4. Simulasi harus berstatus Aktif sebelum dimasukkan ke Program Assessment.
5. Program harus berstatus Aktif agar peserta dapat memulai simulasi.
6. Peserta hanya dapat mengakses program yang secara resmi ditugaskan kepadanya.
7. Asesor hanya dapat mengakses simulasi dan submission dalam penugasannya.
8. File presentasi disimpan pada penyimpanan privat aplikasi.
9. Laporan LGD dan wawancara CI/In-Tray dilaksanakan secara offline.
10. Sistem menolak penghapusan program, simulasi, atau peserta yang memiliki riwayat penggunaan.

## 14. Batasan Versi Sementara

Versi ini belum ditujukan sebagai rilis produksi final. Sebelum implementasi produksi, masih diperlukan antara lain:

- Validasi proses bisnis final bersama pihak client.
- Penyempurnaan kebijakan akun dan password.
- Pengujian penerimaan pengguna atau User Acceptance Test (UAT).
- Pengujian keamanan dan beban.
- Mekanisme backup serta pemulihan data.
- Konfigurasi email dan notifikasi produksi.
- Penyempurnaan laporan dan publikasi hasil.
- Finalisasi alur modul Rekrutmen.

## 15. Penutup

Sistem pada tahap saat ini telah menyediakan fondasi dan alur utama assessment dari penyusunan simulasi hingga penilaian asesor. Dokumen ini akan diperbarui mengikuti hasil evaluasi, keputusan proses bisnis client, dan perkembangan implementasi berikutnya.

---

**Dokumen ini bersifat sementara dan digunakan untuk kebutuhan pembahasan serta laporan progres kepada client.**
