# 📋 Rencana Kerja & Roadmap Pengembangan Fitur: Modul Penilaian PSB Dalwa Kalbar
*(Arsitektur Terpadu Multi-Jenjang & Berbasis Kelompok Ujian)*

Dokumen perencanaan teknis dan operasional komprehensif untuk pembangunan fitur **Penilaian & Ujian Seleksi Calon Santri** pada sistem Penerimaan Santri Baru (PSB) Pondok Pesantren Darullughah Wadda'wah (DALWA) Cabang Kalimantan Barat.

URL Modul: `http://dalwakalbar-psb.test/admin/pendaftar/penilaian`

---

## 📑 DAFTAR ISI
1. [Ringkasan Eksekutif & Karakteristik Lintas Jenjang](#1-ringkasan-eksekutif--karakteristik-lintas-jenjang)
2. [Standar & Konsistensi Desain UI/UX (Merujuk Halaman Interview)](#2-standar--konsistensi-desain-uiux-merujuk-halaman-interview)
3. [Arsitektur Halaman & Navigasi](#3-arsitektur-halaman--navigasi)
4. [Struktur Database & Relasi Model](#4-struktur-database--relasi-model)
5. [Struktur Aspek Penilaian & Rumus Perhitungan](#5-struktur-aspek-penilaian--rumus-perhitungan)
6. [Struktur Formulir Wawancara Komprehensif](#6-struktur-formulir-wawancara-komprehensif)
7. [Fitur Cetak Dokumen & Ekspor Data](#7-fitur-cetak-dokumen--ekspor-data)
8. [Checklist Task & Tahapan Pengerjaan (Roadmap)](#8-checklist-task--tahapan-pengerjaan-roadmap)
9. [Hak Akses (Role & Permission) & Keamanan](#9-hak-akses-role--permission--keamanan)

---

## 1. Ringkasan Eksekutif & Karakteristik Lintas Jenjang

Modul **Penilaian** adalah tahapan inti seleksi akademik dan kepribadian calon santri setelah melalui tahap administrasi dan penjadwalan kelompok ujian (*Set Interview*).

### 🔑 Karakteristik Kunci: Fleksibel & Lintas Jenjang (*Cross-Jenjang Grouping*)
- **Satu Kelompok Ujian Dapat Berisi Berbagai Jenjang:** Dalam alur seleksi PSB Dalwa Kalbar, calon santri dari jenjang berbeda (misalnya MTs dan MA, atau S1 dan S2) dapat digabungkan ke dalam satu **Kelompok Ujian** yang sama untuk diuji oleh tim penguji yang bertugas pada jadwal tersebut.
- **Tanpa Isolasi Tab / Tanpa Offcanvas Drawer:** Berbeda dengan modul pendaftaran awal (Draft, Submit, Tagihan) yang tersekat ketat per jenjang karena urusan pembiayaan, modul **Interview & Penilaian** mengadopsi antarmuka terpadu (*Unified Multi-Jenjang Table*). Seluruh data calon santri yang telah dijadwalkan dapat dilihat dan dinilai secara fleksibel tanpa harus berpindah-pindah tab jenjang.
- **Filter Jenjang Bersifat Opsional (*Toggle Card Filter*):** Admin/Penguji dapat melihat seluruh santri sekaligus, atau mengklik kartu ringkasan jenjang di bagian atas untuk menyaring jenjang tertentu secara instan.

---

## 2. Standar & Konsistensi Desain UI/UX (Merujuk Halaman Interview)

Menyelaraskan struktur tata letak dengan halaman **`Interview/Index.vue`** dan **`Interview/Create.vue`**:

```
+---------------------------------------------------------------------------------------------------------+
| [Header Halaman: Judul, Deskripsi]                                 [Aksi Utama: Input Massal / Export]  |
+---------------------------------------------------------------------------------------------------------+
| [Overview Grid 5 Jenjang (MTs, MA, S1, S2, S3)] (Menampilkan Stat Count, Klik untuk Toggle Filter)      |
+---------------------------------------------------------------------------------------------------------+
| [Toolbar Pencarian Live & Tombol Filter Modal] [Limit Page: 10, 25, 50, 100]                            |
+---------------------------------------------------------------------------------------------------------+
| [Unified DataTable Lintas Jenjang dengan Badge Jenjang & Kelompok Ujian]                                |
|   - Checkbox Seleksi Massal (Floating Bulk Action Bar: Kunci Nilai Massal, Cetak Massal, Export)       |
|   - Kolom: Registrasi/NIK, Calon Santri, Jenjang, Kelompok & Jadwal, Skor Menulis, Skor Membaca,       |
|            Skor Hafalan, Hasil Wawancara (A/C/D), Total Nilai, Rekomendasi Kelas, Status Kunci, Aksi    |
+---------------------------------------------------------------------------------------------------------+
```

### Elemen Desain Utama:
1. **Overview Grid 5 Jenjang (*Interactive Toggle Cards*):**
   - 5 Kartu horizontal di bagian atas: **MTs, MA, S1, S2, S3**.
   - Setiap kartu menampilkan: Logo Jenjang resmi, Kode Jenjang, Total santri peserta interview/penilaian, serta tombol *Filter Jenjang* / indikator *Filter Aktif*.
   - Mengklik kartu yang sama kedua kalinya akan melepas filter (kembali menampilkan semua jenjang).
2. **DataTable Terpadu (*Unified Multi-Jenjang List*):**
   - Menampilkan seluruh peserta seleksi tanpa batasan tab.
   - Kolom Jenjang menampilkan logo kecil + nama jenjang + tingkat/prodi tujuan.
   - Kolom Kelompok Ujian menampilkan nama kelompok, tanggal, waktu, dan lokasi.
3. **Badge & Visual Status Hasil Ujian:**
   - **Skor Kategori (Menulis, Membaca, Hafalan):** Badge nilai angka (0–100) disertai warna predikat (*Baik Sekali: Emerald, Baik: Sky, Cukup: Amber, Kurang: Rose*).
   - **Hasil Wawancara:** Badge Kualifikasi (*A: Memenuhi Kualifikasi [Emerald], C: Bersyarat [Amber], D: Tidak Memenuhi [Rose]*).
   - **Status Penguncian Nilai:** Badge *Draft / Belum Dikunci* (Slate) dan *Terkunci / Final* (Emerald dengan ikon gembok).
4. **Row Actions Menu (Titik Tiga):**
   - 📝 **Input / Edit Nilai:** Membuka lembar penilaian santri.
   - 🎙️ **Formulir Wawancara:** Membuka modal wawancara mendalam.
   - 🔒 **Kunci Nilai / Buka Kunci:** Finalisasi skor calon santri.
   - 📄 **Cetak Surat Hasil Tes:** Menghasilkan Surat Keterangan Hasil Tes Sementara (PDF).
   - 👤 **Detail Pendaftar:** Menuju profil pendaftar.
   - 🔑 **Reset Password & Hapus Data.**

---

## 3. Arsitektur Halaman & Navigasi

Modul Penilaian dibagi menjadi antarmuka utama dan beberapa sub-halaman pendukung:

```
[ /admin/pendaftar/penilaian ] (Index: Unified Multi-Jenjang Table & Metrics)
        │
        ├──► [ /admin/pendaftar/penilaian/kelompok/{id} ] (Halaman Baru: Spreadsheet Input Nilai Massal Kelompok)
        │
        ├──► [ /admin/pendaftar/penilaian/{pendaftar} ] (Halaman Baru: Lembar Detail Nilai Calon Santri)
        │
        ├──► [ Modal: WawancaraModal.vue ] (Formulir Wawancara Digital Komprehensif)
        │
        └──► [ /admin/pendaftar/penilaian/{pendaftar}/cetak-surat-hasil ] (Cetak PDF Surat Hasil Tes Sementara)
```

### A. Halaman Utama (Index View)
- **Path:** `/admin/pendaftar/penilaian`
- **File:** `resources/js/pages/Admin/Pendaftar/Penilaian/Index.vue`
- **Fungsi:** Dashboard penilaian pusat, tabel multi-jenjang, filter kelompok/penguji, dan monitoring status penguncian nilai.

### B. Halaman Baru: Spreadsheet Input Nilai Massal per Kelompok (Spreadsheet View)
- **Path:** `/admin/pendaftar/penilaian/kelompok/{kelompokUjian}`
- **File:** `resources/js/pages/Admin/Pendaftar/Penilaian/Spreadsheet.vue`
- **Fungsi:** Halaman khusus bagi Penguji/Admin untuk menginput seluruh nilai peserta di dalam 1 Kelompok Ujian secara cepat layaknya bekerja di Excel (*keyboard navigation, auto-tabbing, auto-calculation*).
- **Fitur Utama:**
  - Header detail kelompok (Nama Kelompok, Tanggal, Jam, Lokasi, Penguji & Pengawas).
  - Tab Sesi Ujian: **Tes Menulis**, **Tes Membaca Kitab**, **Tes Hafalan Al-Qur'an**, dan **Rangkuman Akhir**.
  - Input tabel interaktif dengan validasi langsung nilai 0–100, kalkulasi bobot otomatis, dan penentuan predikat (*Kurang, Cukup, Baik, Baik Sekali*).
  - Tombol *Simpan Draft* dan *Kunci Nilai Seluruh Kelompok*.

### C. Halaman Baru: Detail Lembar Penilaian Santri Individual (Single Candidate View)
- **Path:** `/admin/pendaftar/penilaian/{pendaftar}`
- **File:** `resources/js/pages/Admin/Pendaftar/Penilaian/Show.vue`
- **Fungsi:** Melihat rekap nilai komprehensif calon santri, detail isian 7 bagian wawancara, nilai per aspek, catatan penguji, dan histori perubahan.

### D. Modal Formulir Wawancara Mendalam (Wawancara Modal)
- **Komponen:** `resources/js/pages/Admin/Pendaftar/Penilaian/Components/WawancaraModal.vue`
- **Fungsi:** Formulir digital wawancara 7 bagian sesuai standar resmi `Formulir_Wawancara_Calon_Santri_Desain.docx`.

### E. Cetak Surat Keterangan Hasil Tes Sementara (PDF View)
- **Path:** `/admin/pendaftar/penilaian/{pendaftar}/cetak-surat-hasil`
- **File:** `resources/views/pdf/surat-hasil-tes-sementara.blade.php`
- **Fungsi:** Menghasilkan dokumen resmi Surat Keterangan Hasil Tes Sementara dengan KOP Dalwa Kalbar dan tanda tangan penguji.

---

## 4. Struktur Database & Relasi Model

> **ATURAN UTAMA (Single Source Migration):** Seluruh perubahan skema tabel wajib dilakukan langsung pada file migrasi `create_..._table` yang bersangkutan tanpa membuat file migrasi baru ber-prefix `add_`/`edit_`.

### 1. Tabel `kategori_penilaians`
- File: `database/migrations/2026_08_11_132617_create_kategori_penilaians_table.php`
- Kolom:
  - `id` (UUID PK)
  - `nama_kategori` (string: "Tes Menulis", "Tes Membaca Kitab", "Tes Hafalan Al-Qur'an", "Wawancara")
  - `kode` (string unique: `MENULIS`, `BACA_KITAB`, `HAFALAN`, `WAWANCARA`)
  - `keterangan` (text nullable)
  - `is_active` (boolean default true)
  - `timestamps`, `softDeletes`

### 2. Tabel `aspek_penilaians`
- File: `database/migrations/2026_08_11_132618_create_aspek_penilaians_table.php`
- Kolom:
  - `id` (UUID PK)
  - `kategori_id` (UUID FK -> `kategori_penilaians.id`)
  - `nama_aspek` (string: e.g. "Ketepatan Ejaan & Huruf", "Kelancaran Membaca", "Tajwid")
  - `bobot` (integer: persentase bobot, misal 30)
  - `urutan` (integer default 0)
  - `timestamps`, `softDeletes`

### 3. Tabel `penilaians`
- File: `database/migrations/2026_08_11_132705_create_penilaians_table.php`
- Kolom:
  - `id` (UUID PK)
  - `pendaftar_id` (UUID FK -> `pendaftars.id`)
  - `aspek_id` (UUID FK -> `aspek_penilaians.id`)
  - `penguji_id` (UUID FK -> `users.id`)
  - `kelompok_ujian_id` (UUID FK nullable -> `kelompok_ujians.id`)
  - `nilai` (decimal 5,2 default 0)
  - `catatan` (text nullable)
  - `timestamps`
  - *Unique constraint:* `['pendaftar_id', 'aspek_id']`

### 4. Tabel `penilaian_wawancaras`
- File: `database/migrations/2026_08_11_132705_create_penilaians_table.php` (atau tabel khusus wawancara):
- Kolom:
  - `id` (UUID PK)
  - `pendaftar_id` (UUID FK unique -> `pendaftars.id`)
  - `penguji_id` (UUID FK -> `users.id`)
  - `kelompok_ujian_id` (UUID FK nullable -> `kelompok_ujians.id`)
  - `keinginan_mondok` (string: "Sendiri", "Orang Tua", "Orang Lain", "Sendiri & Orang Tua")
  - `bersedia_mondok_4_tahun` (boolean)
  - `ijazah_1_tahun_setelah_lulus` (boolean)
  - `alasan_tidak_ijazah` (text nullable)
  - `cita_cita` (string nullable)
  - `kenalan_di_pondok` (json nullable: `[{nama, hubungan}]`)
  - `jam_tidur` (string nullable), `jam_bangun` (string nullable)
  - `kegiatan_malam` (text nullable), `riwayat_penyakit` (text nullable)
  - `aspek_ibadah` (json: `{sholat_5_waktu: 'Sering'|'Jarang'|'Tidak Pernah', sholat_berjamaah, shodaqoh, membantu_orang}`)
  - `bacaan_sholat` (json: `{takbiratul_ihram: 'Baik'|'Cukup'|'Kurang'|'Tidak Bisa', al_fatihah, ...}`)
  - `pelanggaran_perilaku` (json: `{bsn_ot, ghosob, sariqoh, dukhon, khamr, istimna, liwath, ...}`)
  - `prestasi` (json: `{juara_kelas, tidak_naik_kelas, bintang_pelajar, lomba_akademik, lomba_non_akademik}`)
  - `hasil_wawancara` (enum/string: `A` [Memenuhi Kualifikasi], `C` [Memenuhi Kualifikasi Bersyarat], `D` [Tidak Memenuhi Kualifikasi])
  - `rekomendasi_kelas_pondok` (string nullable: "I'dadi", "Ibtidaiyah", "Tsanawiyah", "Belum Ditentukan")
  - `catatan_pewawancara` (text nullable)
  - `timestamps`

### 5. Tabel `hasil_ujians` (Akumulasi Nilai Akhir)
- File: `database/migrations/2026_08_11_132633_create_hasil_ujians_table.php`
- Kolom:
  - `id` (UUID PK)
  - `pendaftar_id` (UUID FK unique -> `pendaftars.id`)
  - `nilai_menulis` (decimal 5,2 default 0)
  - `predikat_menulis` (string nullable)
  - `nilai_baca_kitab` (decimal 5,2 default 0)
  - `predikat_baca_kitab` (string nullable)
  - `nilai_hafalan` (decimal 5,2 default 0)
  - `predikat_hafalan` (string nullable)
  - `nilai_wawancara` (decimal 5,2 default 0)
  - `hasil_wawancara` (string nullable: "A", "C", "D")
  - `total_nilai` (decimal 5,2 default 0)
  - `rekomendasi_kelas_pondok` (string nullable: "I'dadi", "1TS", "2TS", "3TS", "Ibtidaiyah", "Tsanawiyah")
  - `status_kelulusan` (string nullable: "LULUS", "LULUS_BERSYARAT", "TIDAK_LULUS", "MENUNGGU_KEPUTUSAN")
  - `catatan_final` (text nullable)
  - `nomor_surat_hasil` (string nullable)
  - `locked_at` (timestamp nullable)
  - `locked_by` (UUID FK nullable -> `users.id`)
  - `timestamps`, `softDeletes`

---

## 5. Struktur Aspek Penilaian & Rumus Perhitungan

Mengacu pada file acuan client (`HAFALAN.xlsx`, `Rangkuman.xlsx`):

### 1. Kategori: Tes Menulis (Imla/Dikte & Khat) — Bobot 100%
| No | Aspek Penilaian | Bobot | Deskripsi Indikator |
| :--- | :--- | :---: | :--- |
| 1 | Ketepatan Ejaan & Huruf | 30% | Kebenaran kaidah penulisan huruf hijaiyah dan sambungan kata |
| 2 | Kelengkapan Kata & Kalimat | 25% | Kelengkapan teks dikte yang ditulis tanpa kata tertinggal |
| 3 | Keindahan & Kerapian Tulisan | 25% | Kaidah khat, proporsi tulisan, dan kebersihan lembar jawaban |
| 4 | Adab & Kecepatan / Ketepatan Waktu | 15% | Kesiapan mental, adab saat ujian, dan efisiensi waktu |
| 5 | Pemahaman Makna (Opsional) | 5% | Pemahaman dasar atas kalimat yang ditulis |

### 2. Kategori: Tes Membaca Kitab — Bobot 100%
| No | Aspek Penilaian | Bobot | Deskripsi Indikator |
| :--- | :--- | :---: | :--- |
| 1 | Kelancaran Membaca | 20% | Kelancaran membaca teks kitab gundul / berharakat |
| 2 | Ketepatan Harakat & I'rab | 25% | Ketepatan membunyikan harakat akhir dan kedudukan i'rab |
| 3 | Kaidah Nahwu & Sharaf | 25% | Penguasaan wazan, tashrif, dan kaidah gramatika Arab |
| 4 | Terjemah & Pemahaman Isi | 20% | Kemampuan menerjemahkan dan menjelaskan substansi teks |
| 5 | Adab & Kepercayaan Diri | 10% | Sopan santun di hadapan penguji dan ketenangan menjawab |

### 3. Kategori: Tes Hafalan Al-Qur'an — Bobot 100%
| No | Aspek Penilaian | Bobot | Deskripsi Indikator |
| :--- | :--- | :---: | :--- |
| 1 | Kelancaran Hafalan | 30% | Daya ingat hafalan tanpa banyak pengulangan atau jeda |
| 2 | Kaidah Tajwid | 30% | Penerapan hukum nun mati, mim mati, mad, ghunnah, dll |
| 3 | Makharijul Huruf | 20% | Ketepatan tempat keluarnya huruf hijaiyah |
| 4 | Adab & Tartil | 20% | Irama membaca, tartil, dan kesopanan saat membaca Al-Qur'an |

### 4. Skala Penilaian & Predikat Otomatis
| Rentang Nilai | Predikat | Keterangan |
| :---: | :--- | :--- |
| **85.00 – 100.00** | **BAIK SEKALI** | Sangat menguasai materi dan memenuhi seluruh standar kualifikasi |
| **70.00 – 84.99** | **BAIK** | Menguasai materi dengan baik dan memenuhi standar kualifikasi |
| **55.00 – 69.99** | **CUKUP** | Cukup menguasai materi namun memerlukan pembinaan lanjutan |
| **0.00 – 54.99** | **KURANG** | Belum memenuhi kualifikasi standar minimal materi ujian |

---

## 6. Struktur Formulir Wawancara Komprehensif

Mengacu pada `Formulir_Wawancara_Calon_Santri_Desain.docx`:

### Bagian A: Motivasi & Kesiapan
- **Keinginan Mondok:** `Sendiri` | `Orang Tua` | `Orang Lain` | `Sendiri & Orang Tua`
- **Bersedia Mondok Minimal 4 Tahun:** `Ya` | `Tidak`
- **Tidak mengambil Ijazah 1 tahun setelah kelulusan:** `Ya` | `Tidak` (dengan catatan jika Tidak)
- **Cita-cita Calon Santri:** Teks bebas
- **Kenalan di Pondok:** Nama & Hubungan/Status

### Bagian B: Kebiasaan Sehari-Hari & Kesehatan
- **Jam Tidur & Jam Bangun:** Jam harian di rumah
- **Kegiatan di atas jam 22:00 WIB:** Aktivitas malam
- **Riwayat Penyakit:** Penyakit bawaan/menular/kronis

### Bagian C: Ibadah & Keagamaan
1. **Aspek Ibadah Praktis (Sering / Jarang / Tidak Pernah):**
   - Sholat 5 Waktu, Sholat Berjamaah, Shodaqoh, Membantu Orang Lain
2. **Kemampuan 16 Bacaan Sholat (Baik / Cukup / Kurang / Tidak Bisa):**
   - Takbiratul Ihram, Surat Al-Fatihah, Doa Iftitah, Ta'awudz, Membaca Surat/Ayat, Ruku', I'tidal, Sujud, Duduk di Antara Dua Sujud, Tasyahud Awal, Tasyahud Akhir, Sholawat Nabi, Doa Sebelum Salam, Salam Pertama, Wirid Sholat, Doa Sehari-hari.

### Bagian D: Riwayat Pelanggaran & Perilaku (Sering / Pernah / Tidak Pernah)
- BSN OT (Bantah/Sikap Negatif Orang Tua), BSH OT, Ghosob, Sariqoh, Thakossum, Dukhon (Merokok), Khamr (Miras), Mukhoddirot (Narkoba), Jawwal (HP), Kholiah, Istimna', Liwath, Hawian, PT, CP, KB, PS, PK, TB, D.

### Bagian E: Prestasi Calon Santri
- Juara Kelas, Tidak Naik Kelas, Bintang Pelajar, Juara Lomba Akademik, Juara Lomba Non-Akademik.

### Bagian F & G: Keputusan & Rekomendasi Pewawancara
- **Status Kualifikasi:**
  - `A`: Memenuhi Kualifikasi
  - `C`: Memenuhi Kualifikasi dengan Syarat Tertentu
  - `D`: Tidak Memenuhi Kualifikasi
- **Rekomendasi Penempatan Kelas Pondok:**
  - `Kelas I'dadi` (Persiapan Dasar)
  - `Kelas Ibtidaiyah`
  - `Kelas Tsanawiyah` (1TS, 2TS, 3TS)
  - `Belum Ditentukan` (Menunggu evaluasi pusat)
- **Catatan Khusus Pewawancara & Tanda Tangan Digital Penguji**

---

## 7. Fitur Cetak Dokumen & Ekspor Data

### 1. Surat Keterangan Hasil Tes Sementara (PDF)
- Format resmi ber-KOP Pondok Pesantren Darullughah Wadda'wah Cabang Kalimantan Barat.
- Nomor Surat: `[AUTONUMBER] / PPB-KALBAR / [BULAN-ROMAWI] / [TAHUN]`.
- Identitas Calon Santri, Status Wawancara (A/C/D), Hasil 3 Tes Akademik (Predikat & Nilai), Keterangan Penempatan Kelas Sementara, dan Tanda Tangan Panitia PSB.

### 2. Ekspor Rekapitulasi Nilai Kolektif (Excel)
- Sesuai tata letak `Rangkuman.xlsx`:
  - Kolom: No, Nomor Peserta, Nama Calon Santri, Jenjang, Hasil Interview, Catatan Interview, Tes Membaca, Catatan Membaca, Tes Menulis, Catatan Menulis, Tes Hafalan, Catatan Hafalan, Rekomendasi Kelas Pondok, Status Kunci Nilai.

---

## 8. Checklist Task & Tahapan Pengerjaan (Roadmap)

### 🟢 FASE 1: Database & Master Data Seeder
- [ ] **Task 1.1:** Pastikan skema migrasi tabel `kategori_penilaians`, `aspek_penilaians`, `penilaians`, `penilaian_wawancaras`, dan `hasil_ujians` lengkap sesuai spesifikasi single source migration.
- [ ] **Task 1.2:** Buat seeder master aspek penilaian:
  - `database/seeders/Ujian/PenilaianMasterSeeder.php` memuat 5 aspek Menulis, 5 aspek Membaca Kitab, 4 aspek Hafalan, dan Kategori Wawancara.
- [ ] **Task 1.3:** Setup relasi Model Eloquent (`Pendaftar`, `KelompokUjian`, `Penilaian`, `PenilaianWawancara`, `HasilUjian`, `AspekPenilaian`).

### 🟢 FASE 2: Backend Controller & Service Logic
- [ ] **Task 2.1:** Kembangkan `PenilaianPendaftarPageController::index`:
  - Query calon santri peserta interview lintas jenjang (`status = INTERVIEW` atau `has('kelompokUjians')`).
  - Hitung live count per jenjang untuk 5 kartu overview di bagian atas.
  - Eager loading relasi lengkap (cabang, jenjang, kelompokUjians, penilaians, hasilUjian).
- [ ] **Task 2.2:** Kembangkan `PenilaianPendaftarPageController::spreadsheet`:
  - Menampilkan antarmuka input nilai massal seluruh santri di satu kelompok ujian tertentu.
- [ ] **Task 2.3:** Kembangkan `PenilaianPendaftarPageController::storeScore`:
  - Validasi dan penyimpanan nilai angka per aspek serta kalkulasi otomatis total nilai & predikat pada `hasil_ujians`.
- [ ] **Task 2.4:** Kembangkan `PenilaianPendaftarPageController::storeWawancara`:
  - Validasi dan penyimpanan formulir 7 bagian wawancara ke `penilaian_wawancaras`.
- [ ] **Task 2.5:** Kembangkan `finalize` & `bulkFinalize`:
  - Penguncian nilai (`locked_at`, `locked_by`) dengan `DB::transaction`.
- [ ] **Task 2.6:** Kembangkan `unlock`:
  - Fitur buka kunci nilai dengan permission `ujian.penilaian.unlock`.
- [ ] **Task 2.7:** Kembangkan `export`:
  - Export Excel rekap penilaian multi-jenjang.
- [ ] **Task 2.8:** Kembangkan `cetakSuratHasil`:
  - Render PDF Surat Keterangan Hasil Tes Sementara ber-KOP resmi.

### 🟢 FASE 3: Antarmuka Halaman Utama (`Index.vue`)
- [ ] **Task 3.1:** Bangun layout halaman utama:
  - Header halaman & tombol aksi cepat (Export, Buka Input Massal).
  - 5 Kartu Overview Jenjang (MTs, MA, S1, S2, S3) dengan toggle filter interaktif.
- [ ] **Task 3.2:** Integrasikan `DataTable` multi-jenjang:
  - Kolom Registrasi/NIK, Calon Santri & Foto, Jenjang, Kelompok Ujian & Jadwal, Skor Menulis, Skor Membaca, Skor Hafalan, Status Wawancara, Total Nilai & Predikat, Rekomendasi Kelas, Status Kunci, dan Row Action Menu.
- [ ] **Task 3.3:** Sediakan `FilterModal` multi-kriteria (Kelompok Ujian, Penguji, Status Penilaian, Cabang, Periode, Gelombang, Rentang Tanggal).
- [ ] **Task 3.4:** Floating Bulk Action Bar (Kunci Massal, Cetak Massal, Export Data Terpilih).

### 🟢 FASE 4: Halaman Baru Spreadsheet Input Nilai Massal (`Spreadsheet.vue`)
- [ ] **Task 4.1:** Buat halaman `/admin/pendaftar/penilaian/kelompok/{id}` dengan layout Admin terpadu.
- [ ] **Task 4.2:** Buat tabbed spreadsheet: Tes Menulis, Tes Membaca, Tes Hafalan, dan Rekapitulasi Akhir.
- [ ] **Task 4.3:** Fitur input nilai langsung di tabel dengan kalkulasi otomatis predikat & total per baris.
- [ ] **Task 4.4:** Fitur simpan draft dan finalisasi nilai satu kelompok.

### 🟢 FASE 5: Modal Formulir Wawancara Mendalam (`WawancaraModal.vue`)
- [ ] **Task 5.1:** Bangun modal interaktif dengan navigasi langkah/tab untuk 7 seksi wawancara:
  - Bagian A: Motivasi & Kesiapan
  - Bagian B: Kebiasaan Sehari-hari & Kesehatan
  - Bagian C: Aspek Ibadah & 16 Bacaan Sholat
  - Bagian D: 20 Item Pelanggaran & Perilaku
  - Bagian E: Prestasi
  - Bagian F & G: Rekomendasi Kualifikasi (A/C/D) & Penempatan Kelas Pondok.
- [ ] **Task 5.2:** Pencegahan state leaking (`form.reset()` & `form.clearErrors()` saat modal dibuka/ditutup).

### 🟢 FASE 6: Template Cetak PDF & Ekspor Excel
- [ ] **Task 6.1:** Desain template Blade PDF Surat Keterangan Hasil Tes Sementara ber-KOP resmi.
- [ ] **Task 6.2:** Desain export PhpSpreadsheet sesuai tata letak `Rangkuman.xlsx`.

### 🟢 FASE 7: Pengujian, Formatting & Validasi
- [ ] **Task 7.1:** Format kode PHP menggunakan `vendor/bin/pint --format agent`.
- [ ] **Task 7.2:** Build bundle frontend menggunakan `npm run build` dan pastikan 0 error TypeScript/Vue.
- [ ] **Task 7.3:** Uji alur end-to-end: Input nilai -> Form wawancara -> Kalkulasi predikat -> Kunci nilai -> Cetak surat -> Export Excel.

---

## 9. Hak Akses (Role & Permission) & Keamanan

Seluruh endpoint backend dan tombol frontend dikontrol oleh **Spatie Laravel Permission**:

| Permission Key | Deskripsi Akses | Digunakan Pada |
| :--- | :--- | :--- |
| `ujian.penilaian.view` | Melihat daftar pendaftar penilaian & ringkasan skor | Index View, Show Detail |
| `ujian.penilaian.input` | Menginput/mengedit nilai tes akademik & formulir wawancara | Store Score, Spreadsheet View, Wawancara Modal |
| `ujian.penilaian.finalize` | Mengunci nilai akhir (*finalize / lock scores*) | Finalize Button, Bulk Finalize |
| `ujian.penilaian.unlock` | Membuka kembali nilai yang telah dikunci (Admin Khusus) | Unlock Button |
| `ujian.penilaian.export` | Mengunduh file rekap penilaian format Excel | Export Action |
| `ujian.penilaian.print` | Mencetak Surat Keterangan Hasil Tes Sementara (PDF) | Cetak PDF Action |
| `pendaftar.delete` | Menghapus data pendaftar | Destroy, Bulk Destroy |

---

*Dokumen ini disusun sebagai panduan implementasi resmi untuk pengembangan fitur Penilaian PSB Dalwa Kalbar.*
