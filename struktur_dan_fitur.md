# Struktur Sidebar, Fitur, dan Navigasi Sistem PSB Dalwa Kalbar

Dokumen ini memuat arsitektur menu *sidebar* beserta detail fitur, interaksi pengguna (*action*), dan navigasi untuk 2 (dua) aktor utama dalam sistem, yaitu **Pegawai (Admin/Staf)** dan **Pendaftar (Calon Santri)**.

---

## 🏛️ BAGIAN I: AKTOR PEGAWAI (ADMIN / STAF)

Menu untuk Pegawai menggunakan konsep **Role & Permission**. Jika pegawai tidak memiliki izin (*permission*) untuk suatu modul, maka menu tersebut akan disembunyikan otomatis dari *sidebar*.

### 1. Dashboard (Beranda)
* **Level Sidebar:** 1
* **Fitur di Halaman:** Menampilkan ringkasan dan statistik data pendaftar (jumlah pendaftar per status, total tagihan lunas/belum, grafik pendaftaran).
* **Aksi:** Filter rentang waktu statistik.
* **Navigasi:** Tetap di halaman yang sama.

---

### 2. Manajemen Pendaftar
* **Level Sidebar:** 1

#### 2.1. Pendaftar Draft
* **Level Sidebar:** 2
* **Fitur di Halaman:** Menampilkan tabel pendaftar yang masih dalam tahap pengisian biodata (belum klik submit). Menggunakan komponen `DataTable` dengan tab jenjang (MTs, MA, S1, S2, S3).
* **Daftar Aksi & Navigasi:**
  * **Pencarian & Limit Tabel:** Pencarian NIK/Nama (tetap di halaman).
  * **Cetak Kartu:** Buka Tab/Halaman baru menampilkan PDF/Print Kartu Pendaftaran.
  * **Detail:** Buka Halaman Baru (Halaman Detail Pendaftar).
  * **Reset Password:** Buka Modal (Input password baru & konfirmasi, default tanggal lahir).
  * **Hapus Data:** Buka Modal (Konfirmasi Hapus dengan icon warning merah).
  * **Checkbox Bulk Action:** Munculkan tombol Export Excel & Hapus Multiple di atas tabel.

> **📄 Sub-Halaman: Detail Pendaftar**
> * **Navigasi dari:** Tombol "Detail" pada baris tabel pendaftar.
> * **Fitur di Halaman:** Tampilan ringkasan komprehensif profil pendaftar.
> * **Isi Konten:** Foto profil, status pendaftaran saat ini, Tab Data Pribadi & Alamat, Tab Data Orang Tua & Wali, Tab Jenjang Pilihan & Riwayat Pendidikan, Tab Dokumen Lampiran, Tab Virtual Account Bank, Tab Riwayat Pendaftaran.
> * **Aksi:** Kembali ke halaman sebelumnya.

#### 2.2. Pendaftar Submit
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel pendaftar yang sudah finalisasi pengisian biodata dan sedang menunggu verifikasi panitia. Tersedia tab filter jenjang.
* **Daftar Aksi & Navigasi:**
  * Sama seperti Pendaftar Draft (Cetak Kartu, Detail, Reset Pass, Hapus, Export, Pencarian).
  * **Tolak/Terima Pendaftaran (Baru):** 
    * Buka Modal Verifikasi.
    * **Jika Tolak:** Menampilkan Textarea catatan revisi -> Klik Simpan -> Pendaftar kembali ke status Draft.
    * **Jika Terima:** Klik Simpan -> Pendaftar otomatis masuk ke tahap Tagihan.

#### 2.3. Pendaftar Tagihan
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel pendaftar yang telah lolos pemberkasan dan wajib membayar biaya tes/pendaftaran. Memiliki kolom informasi tambahan: Status Pembuatan Tagihan (Dibuat/Belum), Status Pembayaran Tagihan (Lunas/Belum/Samaha), Sisa Tagihan, Status Verifikasi (Menunggu, Diterima, Ditolak).
* **Daftar Aksi & Navigasi:**
  * **Terima/Tolak Pembayaran:** (Hanya muncul jika pendaftar mengupload bukti transfer). Buka Modal Verifikasi Pembayaran. Menampilkan informasi Invoice, Nominal, Bank, Bukti Transfer.
  * **Buat Tagihan:** Buka Halaman Baru (Halaman Form Tagihan).
  * **Detail Tagihan:** Buka Halaman Baru (Halaman Detail & Riwayat Cicilan Tagihan).
  * **Aksi Standar:** Cetak Kartu, Detail Pendaftar, Hapus, Export Excel.

> **📄 Sub-Halaman: Buat Tagihan**
> * **Navigasi dari:** Tombol "Buat Tagihan" di tabel Pendaftar Tagihan.
> * **Fitur di Halaman:** 
>   * *Card Form Tagihan:* Input Nama Tagihan, Jenis, Nominal, Tanggal Terbit, Jatuh Tempo.
>   * *Card Daftar Penerima Tagihan:* Tabel siswa yang ditagihkan.
> * **Aksi:** 
>   * **Tambah Pendaftar:** Buka Modal (Pencarian NIK/Nama, checkbox, tambahkan ke tabel).
>   * **Input Samaha (Potongan):** Langsung di baris tabel pendaftar terkait (Otomatis mencatat payment jenis 'Samaha').
>   * **Simpan Tagihan:** Submit ke database, kembali ke halaman Pendaftar Tagihan.

> **📄 Sub-Halaman: Detail Tagihan (Riwayat Pembayaran)**
> * **Navigasi dari:** Tombol "Detail Tagihan" di tabel Pendaftar Tagihan.
> * **Fitur di Halaman:** Menampilkan 3 Card (Info Tagihan, Info Pendaftar, Tabel Riwayat Pembayaran).
> * **Aksi pada Tabel Riwayat Pembayaran:**
>   * **Detail:** Buka Modal melihat detail pembayaran.
>   * **Edit Pembayaran (Khusus Tunai/Samaha):** Buka Modal (Form edit nominal dan keterangan).
>   * **Verifikasi Pembayaran (Khusus Transfer):** Buka Modal (Tolak/Terima Bukti Transfer). Jika tolak, wajib isi alasan penolakan.

#### 2.4. Set Interview
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel pendaftar yang tagihannya telah berstatus Lunas.
* **Daftar Aksi & Navigasi:**
  * **Tambah Jadwal Interview:** Buka Halaman Baru (Halaman Set Jadwal Interview).
  * **Aksi Standar:** Detail, Cetak Kartu, Reset Pass.

> **📄 Sub-Halaman: Tambah Jadwal Interview**
> * **Navigasi dari:** Tombol "Tambah Jadwal Interview".
> * **Fitur di Halaman:** Card Form Jadwal Interview (Pilih Penguji, Tanggal, Waktu) dan Card Daftar Peserta Interview.
> * **Aksi:** Pilih pendaftar (checkbox bulk) -> Simpan -> Kembali ke halaman sebelumnya.

#### 2.5. Manajemen Interview & Penilaian
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel berisi daftar Kelompok/Jadwal Interview. Jika yang login adalah *Penguji*, dia hanya melihat jadwal miliknya.
* **Aksi:**
  * **Mulai Penilaian:** Buka Halaman Baru (Halaman Input Nilai / Spreadsheet View).

> **📄 Sub-Halaman: Halaman Penilaian (Spreadsheet View)**
> * **Navigasi dari:** Tombol "Mulai Penilaian".
> * **Fitur di Halaman:** Tabel interaktif (seperti Excel) berisi nama pendaftar di kelompok tersebut, lengkap dengan kolom nilai: Menulis (Latin, Arab, dll), Membaca Kitab (Kelancaran, Nahwu, dll), Hafalan.
> * **Aksi:** 
>   * **Input Langsung:** Pengetikan nilai di sel tabel secara langsung.
>   * **Nilai Wawancara:** Klik tombol Wawancara -> Buka Modal Form khusus wawancara.
>   * **Kunci Nilai:** Tombol finalisasi (menyatakan semua dinilai).

#### 2.6. Pengumuman Kelulusan
* **Level Sidebar:** 2
* **Fitur di Halaman:** Manajemen penentuan kelulusan peserta tes yang nilainya sudah dikunci.
* **Aksi:** Tentukan Lulus/Tidak Lulus, Cetak Surat Hasil Tes Sementara kolektif.

---

### 3. Manajemen Keberangkatan (Rombongan)
* **Level Sidebar:** 1

#### 3.1. Rombongan Pesawat
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel pendaftar yang lulus tes dan terdaftar untuk diberangkatkan via udara (Pesawat).
* **Daftar Aksi & Navigasi:**
  * Tambah Jadwal Penerbangan (Buka Modal).
  * Tetapkan Titik Kumpul (Buka Modal).
  * Export/Print Daftar Manifes Penumpang Pesawat.
  * Konfirmasi Keberangkatan.

#### 3.2. Rombongan Kapal
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel pendaftar yang lulus tes dan terdaftar untuk diberangkatkan via jalur laut (Kapal).
* **Daftar Aksi & Navigasi:**
  * Tambah Jadwal Pelayaran (Buka Modal).
  * Tetapkan Pelabuhan Kumpul (Buka Modal).
  * Export/Print Daftar Manifes Penumpang Kapal.
  * Konfirmasi Keberangkatan.

#### 3.3. Kedatangan & Tes Kesehatan
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel santri baru (baik dari jalur Rombongan Kapal, Pesawat, maupun Mandiri) yang tiba di pondok Dalwa.
* **Aksi:** 
  * Check-in Setiba di Pondok (Buka Modal).
  * Update Hasil Kesehatan (Buka Modal -> Input Lulus/Gagal). 
  * Jika Lulus, form sinkronisasi data (Penempatan Asrama dan Nomor Kamar) otomatis muncul.

---

### 4. Keuangan & Biaya
* **Level Sidebar:** 1

#### 4.1. Daftar Bank & Biaya Admin Bank
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel daftar bank (Logo, Kode, Nama, Total Fee, Status). Menggunakan sistem `Collapse Row` untuk melihat detail biaya admin.
* **Daftar Aksi & Navigasi:**
  * **Tambah/Edit Bank:** Buka Modal (Upload logo, input kode, switch aktif/nonaktif).
  * **Buka Collapse (Detail Biaya Admin):** Memunculkan sub-tabel di dalam baris (menampilkan jenis biaya dan nominal).
  * **Tambah/Edit Biaya Admin:** Buka Modal khusus di dalam context bank tersebut.

#### 4.2. Jenis Biaya & Rincian Tagihan
* **Level Sidebar:** 2
* **Fitur di Halaman:** Manajemen komponen tagihan. Menggunakan struktur Tab Jenjang (MTs, MA, S1, S2, S3).
* **Daftar Aksi & Navigasi:**
  * Sama seperti Daftar Bank, menggunakan mekanisme `Collapse Row`. Baris tabel utama adalah "Nama Jenis Pembayaran", di-klik memunculkan sub-tabel "Daftar Item Biaya".
  * **Tambah/Edit Jenis Pembayaran:** Buka Modal.
  * **Tambah/Edit Item Biaya:** Buka Modal.

#### 4.3. Virtual Account Pendaftar
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel master list VA (Virtual Account) seluruh siswa untuk berbagai bank yang diaktifkan.
* **Daftar Aksi & Navigasi:**
  * **Tambah VA:** Buka Modal. Didalamnya ada form *live search* siswa (NIK/Nama). Input VA tiap bank akan dalam keadaan `disabled` sebelum nama siswa berhasil dipilih.
  * **Import Excel VA:** Buka Halaman Baru (Halaman Preview Import VA).
  * **Export Template & Data:** Buka Modal.

---

### 5. Laporan & Riwayat Sistem (Log History)
* **Level Sidebar:** 1
* Menu ini memuat laporan historikal final dari seluruh operasional PSB (Read-only data atau dengan fitur filtering & export tingkat lanjut).

#### 5.1. Riwayat Pendaftaran
* **Level Sidebar:** 2
* **Fitur di Halaman:** Menampilkan *timeline* status seluruh pendaftar (kapan mendaftar, kapan disetujui, kapan pindah jenjang). Sangat berguna untuk tracking data tahun akademik sebelumnya.
* **Aksi:** Filter Tahun Akademik, Export Laporan Pendaftaran.

#### 5.2. Riwayat Tagihan
* **Level Sidebar:** 2
* **Fitur di Halaman:** Rekapitulasi seluruh tagihan yang pernah diterbitkan (Lunas maupun Belum Lunas).
* **Aksi:** Filter by Status Tagihan, Filter Rentang Waktu, Export Laporan Tagihan.

#### 5.3. Riwayat Pembayaran
* **Level Sidebar:** 2
* **Fitur di Halaman:** Buku kas/riwayat setiap transaksi uang yang masuk (Tunai, Transfer, maupun Samaha) yang telah berstatus Diterima/Diverifikasi.
* **Aksi:** Detail Transaksi (Buka Modal), Filter Transaksi Harian/Bulanan, Export Laporan Keuangan.

#### 5.4. Riwayat Interview
* **Level Sidebar:** 2
* **Fitur di Halaman:** Arsip daftar nilai tes akademik dan wawancara peserta secara historikal, termasuk data penguji yang bertugas saat itu.
* **Aksi:** Export Rekap Nilai Interview.

#### 5.5. Riwayat Rombongan
* **Level Sidebar:** 2
* **Fitur di Halaman:** Arsip manifest perjalanan rombongan (Pesawat & Kapal) dari gelombang-gelombang sebelumnya.
* **Aksi:** Detail Rombongan, Export Manifest Rombongan.

---

### 6. Akademik & Pengaturan Pendaftaran
* **Level Sidebar:** 1

#### 6.1. Program Pendidikan (Jenjang, Fakultas, Jurusan)
* **Level Sidebar:** 2
* **Fitur di Halaman:** Manajemen hierarki akademik. Menggunakan Tab (MTs, MA, S1, S2, S3).
* **Daftar Aksi & Navigasi:**
  * **Tab MTs:** Tabel Tingkat (Aksi Tambah/Edit via Modal, pengaturan izinkan Gender L/P/Keduanya).
  * **Tab MA:** Memiliki 2 Tabel sekaligus (Grid layout 2:4). Tabel 1 untuk Tingkat MA, Tabel 2 untuk Jurusan MA (dilengkapi setting Gender L/P). Aksi Tambah/Edit via Modal.
  * **Tab S1, S2, S3:** Sama dengan MA, memiliki 2 Tabel (Tabel Fakultas dan Tabel Program Studi).

#### 6.2. Tahun Akademik & Periode Pendaftaran
* **Level Sidebar:** 2
* **Fitur di Halaman:** Manajemen pembukaan pendaftaran. Menggunakan `Collapse Row`.
* **Daftar Aksi & Navigasi:**
  * **Tabel Induk (Tahun Akademik):** Buka Modal (Tambah/Edit Nama Tahun Akademik, Set Aktif/Nonaktif).
  * **Sub-Tabel (Periode Pendaftaran):** Buka `Collapse Row` -> Muncul tabel Gelombang/Periode. Buka Modal untuk Tambah/Edit (Tanggal Buka, Tanggal Tutup, Kuota, Status).
  * **Setting Periode:** Buka Modal (Checklist Jalur Pendaftaran, Checklist Jenjang, Gelombang).

#### 6.3. Dokumen Lampiran Pendaftaran
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel persyaratan berkas (Gambar/PDF, Wajib/Opsional, Jalur, Jenjang). Terdapat kolom indikator "Jadikan Foto Profil".
* **Daftar Aksi & Navigasi:**
  * **Tambah/Edit Lampiran:** Buka Modal (Input nama dokumen, Select jenis JPG/PDF, Switch Wajib/Opsional, Checkbox peruntukan Jenjang MTs/MA/S1/dll, Checkbox "Jadikan Foto Profil"). Hapus via Modal Konfirmasi.

---

### 7. Master Data
* **Level Sidebar:** 1

Seluruh menu di bawah ini memilki tampilan yang seragam, yaitu Tabel Master standar.
* **7.1. Ukuran Baju** (Level 2)
* **7.2. Pendidikan Terakhir Orang Tua** (Level 2)
* **7.3. Penghasilan Orang Tua** (Level 2)
* **7.4. Pekerjaan Orang Tua** (Level 2)
  * **Fitur Unik:** Pada Form (Modal Tambah/Edit) terdapat Checkbox "Pekerjaan Lainnya" (Hanya boleh ada 1 di database). Jika diklik, input nama terkunci menjadi "Pekerjaan Lainnya".
* **7.5. Pendidikan Pendaftar Sebelumnya** (Level 2)
  * **Fitur Unik:** Memiliki `Collapse Row` untuk menambah/edit tingkatan (misalnya Jenjang: Umum -> Sub-tingkatan: SD, SMP, SMA).

**Aksi Umum di atas:** Pencarian, Tambah Data (Modal), Edit Data (Modal), Hapus Data (Modal).

---

### 8. Pengaturan Sistem
* **Level Sidebar:** 1

#### 8.1. Role & Permission
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel Daftar Role (Super Admin kebal hapus). Fitur `Collapse Row` untuk melihat semua permission yang nempel pada role tersebut.
* **Daftar Aksi & Navigasi:**
  * **Tambah/Edit Role:** Buka Modal. Di dalam modal terdapat input teks (Nama Role) dan Checkbox Tree kompleks berbasis Modul.Action. Terdapat aksi "Klik Nama Modul" untuk otomatis menceklis seluruh Action di dalamnya. Hapus via Modal Konfirmasi.

#### 8.2. Pegawai
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel master user pegawai (staf). 
* **Daftar Aksi & Navigasi:**
  * **Aksi Tabel:** Edit, Hapus, Detail, Aktif/Nonaktifkan (semua via button/dropdown).
  * **Reset Password Pegawai:** Buka Modal (Default isi password adalah tanggal lahir ybs).
  * **Atur Role Pegawai:** Buka Modal (Menampilkan Select dropdown role yang tersedia).
  * **Tambah/Edit Pegawai:** Buka Halaman Baru.
  * **Import Excel:** Buka Halaman Baru (Halaman Preview Data Table persis seperti Import VA).

> **📄 Sub-Halaman: Form Pegawai (Tambah/Edit)**
> * **Navigasi dari:** Tombol "Tambah Pegawai" atau "Edit" di tabel.
> * **Fitur di Halaman:** Form utuh layar (Bukan modal). Mengelola input teks NIP, NIK, Biodata, Alamat dinamis dari DB Laravolt.
> * **Fitur Gambar:** Upload Foto Pegawai langsung terhubung dengan fitur Crop In-Browser rasio 1:1. 

#### 8.3. Log System (Aktivitas)
* **Level Sidebar:** 2
* **Fitur di Halaman:** Tabel log sistem (Auditing Trail) mencatat segala manipulasi data yang terjadi.
* **Daftar Aksi & Navigasi:**
  * **Filter Log:** Buka Modal Filter (Filter by User, Rentang Waktu, Role, Modul, Action).
  * **Kosongkan Log:** Menghapus seluruh riwayat log (Buka Modal Konfirmasi berisiko tinggi).
  * **Export:** Langsung terunduh ke Excel.
  * **Detail:** Buka Halaman Baru / Modal Besar (Menampilkan Before & After Data JSON dalam bentuk readable, IP, User Agent).

#### 8.4. Konfigurasi Sistem
* **Level Sidebar:** 2
* **Fitur di Halaman:** Form pengaturan variabel global sistem (Generasi Nomor Pendaftaran `PSB-[YYYY]-[AUTONUMBER]`, Nomor WhatsApp Layanan Lupa Password bagi pendaftar, Logo KOP Surat cetak).
* **Navigasi:** Tetap di halaman, klik simpan perubahan.


---
<br>

## 🧑‍🎓 BAGIAN II: AKTOR PENDAFTAR (CALON SANTRI)

Sidebar untuk pendaftar didesain mandiri, intuitif, dan menuntun langkah pendaftar (Step-by-step). Menu-menu ini berfungsi mengubah status pendaftar dari **Draft** menuju **Santri Aktif**.

### 1. Beranda (Dashboard Pendaftar)
* **Level Sidebar:** 1
* **Fitur di Halaman:** 
  * Menampilkan informasi sambutan, timeline pendaftaran (progress bar), dan pengingat kelengkapan data. 
  * Pesan notifikasi darurat (misalnya: "Tagihan Belum Lunas").
* **Aksi:** Tombol jalan pintas "Lengkapi Biodata Sekarang" atau "Bayar Tagihan" (mengarahkan ke menu yang dituju).

### 2. Biodata Diri (Multi-Step Form)
* **Level Sidebar:** 1
* **Fitur di Halaman:** Halaman form ber-wizard (Terdapat Tab/Indikator langkah di atas halaman).
  * **Step 1: Data Personal.** Input Foto Profil, Cabang (Kalbar/Kalsel), NIK, KK, Hobi, Cita-cita, Saudara.
  * **Step 2: Data Orang Tua.** Dinamis (Ayah/Ibu Status Hidup/Meninggal mempengaruhi wajib/tidaknya field di bawahnya). Wali opsional (kecuali Ortu meninggal).
  * **Step 3: Alamat.** Form dengan database dinamis Provinsi -> Kabupaten -> Kecamatan -> Desa (Select Cascade).
  * **Step 4: Riwayat Pendidikan.** Jika memilih MA, muncul select jurusan MA. Jika pilih S1, wajib pilih Fakultas Utama & Alternatif. Terhubung dengan form data Sekolah Sebelumnya.
* **Daftar Aksi & Navigasi:** 
  * **Tombol Lanjut & Kembali:** Berpindah antar step (Di halaman yang sama namun komponen berganti, atau SPA navigation).
  * **Simpan Finalisasi (Submit):** Buka Modal Konfirmasi persetujuan syarat. Setelah di-submit, form terkunci mati (Read-only). Status pendaftar naik menjadi **Submit**.

### 3. Lampiran Dokumen
* **Level Sidebar:** 1
* **Fitur di Halaman:** Menampilkan daftar dokumen yang WAJIB dan OPSIONAL diunggah, berdasarkan settingan panitia untuk jenjang/jalurnya.
* **Aksi:**
  * **Upload/Re-upload:** Buka dialog upload file asli (Menerima JPG/PDF sesuai pengaturan Admin).
  * **Lihat Berkas:** Preview gambar/PDF yang diunggah.

### 4. Keuangan (Tagihan & Pembayaran)
* **Level Sidebar:** 1
* **Fitur di Halaman:** 
  * Tabel tagihan Invoice milik pendaftar. Menampilkan Sisa Tagihan, Daftar Virtual Account miliknya.
  * Riwayat/Buku Cicilan Pembayaran.
* **Daftar Aksi & Navigasi:**
  * **Konfirmasi Bayar / Upload Bukti Transfer:** Buka Modal. Memilih tagihan, mengisi nominal yang ditransfer, tujuan bank panitia, dan melampirkan foto resi transfer. Menunggu admin melakukan verifikasi.

### 5. Jadwal Ujian & Hasil
* **Level Sidebar:** 1
* **Fitur di Halaman:** Menampilkan jadwal waktu, tanggal, dan penguji untuk tahapan Set Interview. 
* **Aksi:**
  * **Cetak Surat Pengumuman / Hasil Tes:** Jika admin sudah mengetok palu kelulusan, muncul tombol Download PDF (yang meng-generate surat kelulusan berkop resmi Dalwa Kalbar).

### 6. Keberangkatan & Rombongan
* **Level Sidebar:** 1
* **Fitur di Halaman:** (Hanya terbuka jika pendaftar berstatus LULUS Tes). Menampilkan form pemesanan tiket rombongan atau melapor jalur mandiri. Pilihan terbagi menjadi **Pesawat** dan **Kapal**.
* **Aksi:** Memilih titik kumpul pelabuhan/bandara dan konfirmasi pemberangkatan bersama.

---

> **⚙️ Catatan Arsitektur:** Seluruh form (Pegawai maupun Pendaftar) di-desain menggunakan elemen *UI Standard* (Rounded 2xl, Button Rounded Full, Focus Ring, Dropdown Action modern) seperti yang dimandatkan di dokumen standarisasi. Input Select, Date, Checkbox, dan Modal diletakkan dalam *Component Reusable* (`Vue.js + Inertia`). Modal tidak akan tertutup bila user meng-klik sisi luar (*backdrop static*). Semua interaksi berjalan asinkronus (SPA) tanpa *page-reload*.
