# Product Requirements Document (PRD) - Sistem Penerimaan Santri Baru (PSB) Dalwa Kalbar

Sistem ini adalah aplikasi berbasis web untuk mengelola penerimaan santri baru secara komprehensif, mulai dari pendaftaran awal, pembayaran, seleksi (wawancara dan tes akademik), hingga keberangkatan (rombongan) dan penerimaan akhir di pondok.

Sistem dirancang secara *Dynamic & Configuration-Driven*, menggunakan UI Component base (sehingga konsisten), UUID untuk seluruh primary key, dan otorisasi berbasis Role & Permission (Spatie).

---

## 1. Arsitektur & Teknologi Utama
- **Backend:** Laravel 11, PHP 8.3, PostgreSQL/MySQL.
- **Frontend:** Vue.js 3 (Composition API), Inertia.js (v3), TailwindCSS.
- **Database PK:** UUID (`Str::uuid()`).
- **Otorisasi:** Spatie Laravel Permission (berbasis modul.action).
- **Pendekatan UI:** Component-based (Input, Select, Table, Modal standar).
- **Animasi:** Animasi halus dan transisi interaktif.

---

## 2. Aktor & Autentikasi

### A. Pegawai (Admin/Staf)
- **Login:** Menggunakan **Email** dan **Password**.
- **Lupa Password:** Fitur standar email reset link.
- **Profil:** Pegawai dapat mengedit seluruh profil mereka sendiri (Foto, Nama, Kontak, dll).
- **Hak Akses:** Dibatasi oleh sistem Role & Permission. Menu sidebar disembunyikan otomatis jika tidak memiliki akses (permission).

### B. Pendaftar
- **Registrasi Awal:** Hanya mengisi **Nama, NIK, dan Password**.
- **Login:** Menggunakan **NIK** atau **Nomor Pendaftaran** dan **Password**.
- **Dashboard:** Pendaftar memiliki sidebar mandiri (mirip admin) dengan menu-menu khusus pendaftar untuk melanjutkan pengisian biodata secara bertahap.
- **Lupa Password:** Tidak menggunakan email reset link. Menampilkan **Nomor Kontak WhatsApp/Telepon** (yang dapat diatur di Setting) agar pendaftar dapat menghubungi panitia untuk reset password.
- **Profil:** Pendaftar tidak memiliki akses edit profil bebas; data dikunci setelah di-*submit* kecuali dibuka kembali oleh Admin (Draft).
- **Password:** Semua input password di sistem harus memiliki toggle *hide/show*.

---

## 3. Ketentuan Database Khusus & Validasi
- **Unique tapi Nullable:** Beberapa kolom bersifat unik namun opsional (boleh dikosongkan). Sistem harus menangani agar nilai kosong/NULL tidak memicu galat *Unique Constraint*.
- **Nomor Pendaftaran & Invoice:** Menggunakan format/pola yang dinamis, dapat diatur melalui halaman **Pengaturan Sistem**.

---

## 4. Struktur Halaman & Fitur (Admin / Pegawai)

Modul-modul ini menggunakan struktur antarmuka yang konsisten:
- **Tabel:** Terdapat pencarian (kiri atas), opsi jumlah baris (5, 10, 25, 50, 100), filter via modal (ada tombol Simpan & Reset), dan aksi di kanan ujung (bisa *overlay canvas* di mobile, atau *dropdown/icon* rapi di desktop).
- **Modal:** Modal tidak bisa ditutup dengan klik di luar layar (backdrop static), hanya bisa ditutup via tombol silang atau "Batal".
- **Export/Import:** Export/Import format Excel. Angka panjang diekspor sebagai string (teks) agar tidak berantakan di Excel.

### A. Log System
- **Fitur:** Menampilkan riwayat aksi pengguna.
- **Aksi:** Cekbox (pilih semua), Hapus (via modal), Export, Kosongkan Log, Filter (User, Waktu, Role, Modul).
- **Detail:** Kolom action "Detail" memunculkan halaman/modal yang menampilkan komparasi data sebelum dan sesudah (*before-after*), IP, User Agent.

### B. Role & Permission
- **Tabel:** Menampilkan daftar Role (Super Admin tidak bisa dihapus). Baris memiliki fitur *collapse* untuk melihat daftar *permission* yang dimiliki.
- **Aksi:** Tambah, Edit, Hapus.
- **Form:** Nama Role dan *checkbox list* permission (dikelompokkan dengan format `modul.action` misal `staf.create`). Ada fitur klik nama modul untuk ceklis semua *action* di bawahnya.

### C. Pegawai
- **Fitur Utama:** Manajemen data Staf (Foto, NIK, NIP, Alamat, dll).
- **Alamat:** Menggunakan database lokasi (Laravolt Indonesia) dari Provinsi hingga Desa.
- **Aksi Tabel:** Detail, Edit, Hapus, Reset Password (via modal, default pass = tanggal lahir), Atur Role, Aktif/Nonaktif.
- **Tambah/Edit Pegawai:** Halaman baru (bukan modal) dengan fitur *crop image* 1:1. Tidak ada input role di form ini (diatur terpisah).
- **Import/Export:** Import via template Excel, preview di tabel dengan validasi langsung (invalid feedback) dan tombol hapus baris. Export bisa mengunduh template resmi.

### D, E, F, G, H. Master Data Pendukung (Tabel Master)
- Menggunakan skema Modal untuk Tambah, Edit, dan Hapus.
- **D. Ukuran Baju**
- **E. Pendidikan Terakhir Orang Tua**
- **F. Pekerjaan Orang Tua:** Ada opsi khusus "Pekerjaan Lainnya" (Hanya boleh 1 di database).
- **G. Pendidikan Pendaftar Sebelumnya:** Arow *collapse* untuk melihat tingkatan pendidikan di bawahnya.
- **H. Penghasilan Orang Tua:** Rentang penghasilan dalam bentuk teks.

### I. Program Pendidikan (Jenjang, Kelas/Tingkat, Fakultas, Jurusan/Prodi)
- Diatur berdasarkan tab Jenjang: **MTs, MA, S1, S2, S3**.
- **Tab MTs:** Tabel Kelas/Tingkat. Pengaturan gender (hanya L, hanya P, atau Keduanya).
- **Tab MA:** Tabel Tingkat & Tabel Jurusan (Layout 2:4). Jurusan memiliki batasan gender.
- **Tab S1, S2, S3:** Tabel Fakultas & Tabel Program Studi (Layout 2:4).

### J. Dokumen Lampiran Pendaftaran
- Menentukan syarat berkas (Gambar/PDF), sifat (Wajib/Opsional), jalur, dan jenjang.
- Pengaturan khusus: checkbox "Jadikan Foto Profil" (hanya 1 dokumen tipe gambar yang bisa dipilih).

### K. Tahun Akademik & Periode Pendaftaran
- **Tahun Akademik:** *Collapse row* menampilkan daftar **Periode Pendaftaran**.
- **Periode:** Mengatur status (Buka, Tutup, Draft), Kuota, Rentang Tanggal. Ada "Setting" (modal) untuk memilih jalur, jenjang, dan gelombang pendaftaran yang aktif pada periode tersebut.

### L. Daftar Bank & Biaya Admin Bank
- Tabel Bank (logo, nama bank, status). *Collapse row* menampilkan daftar Biaya Admin (jenis biaya & nominalnya) untuk bank tersebut.

### M. Jenis Biaya dan Daftar Biaya (Tagihan)
- Dibagi dalam Tab Jenjang. 
- *Collapse row* Jenis Pembayaran memunculkan rincian item biaya (nama biaya, nominal).

### N. Virtual Account Pendaftar
- Mengelola VA spesifik per siswa untuk tiap bank.
- Dapat diimport dari Excel. Form tambah menggunakan pencarian *live* ke data siswa (sebelum pendaftar dipilih, input VA disabled).

---

## 5. Flow Pendaftaran & Manajemen Calon Santri

Pendaftar memiliki status berjenjang:
1. **Draft:** Baru mendaftar, belum klik finalisasi.
2. **Submit:** Sudah difinalisasi, menunggu verifikasi berkas oleh panitia.
3. **Tagihan (Belum Lunas):** Berkas diterima, tagihan (invoice) sudah terbit namun belum dibayar lunas.
4. **Set Interview:** Tagihan lunas, dijadwalkan untuk tes wawancara dan akademik.
5. **Interview / Penilaian:** Mengikuti ujian dan di-input nilainya oleh penguji.
6. **Lulus / Gagal Tes:** Hasil dari akumulasi ujian akademik & wawancara.
7. **Rombongan / Keberangkatan:** Jika lulus, memesan tiket rombongan atau jalur mandiri.
8. **Tes Kesehatan (Tiba di Dalwa):** Diperiksa bebas narkotika/penyakit setibanya di Dalwa.
9. **Santri Aktif:** Dinyatakan lulus kesehatan, kemudian di-sinkronisasi. "Nama Pondok", "Nama Asrama", "Kamar" akan dilengkapi pada tahap ini.

Setiap tahapan status memiliki Menu/Tab terpisah di dasbor Admin:

### O. Pendaftar Draft
- **Fitur Tabel:** Cetak Kartu, Detail, Reset Password, Hapus. Ekspor Excel.

### P. Pendaftar Submit
- **Aksi Baru:** "Terima/Tolak Pendaftaran".
- Jika **Ditolak:** Muncul modal input alasan penolakan, status kembali ke **Draft**.
- Jika **Diterima:** Status berubah menjadi **Tagihan**.

### R. Pendaftar Tagihan
- **Status Tambahan:** Lunas / Belum Lunas / Samaha (Potongan).
- **Aksi:**
  - **Terima/Tolak Pembayaran:** Jika transfer, memverifikasi bukti yang dikirim siswa terhadap VA. Jika tunai/samaha, melihat nama staf yang mengurus.
  - **Buat Tagihan:** Halaman khusus merakit Invoice (Nama, Nominal, Jatuh Tempo). Di halaman yang sama terdapat tabel daftar siswa yang dikenakan tagihan ini (ditambahkan via modal pencarian NIK/Nama). Jika siswa mendapat potongan, langsung buatkan "Payment" berjenis Samaha.
  - **Detail Tagihan:** Halaman melihat riwayat cicilan/pembayaran siswa. Terdapat form edit pembayaran (tunai) atau verifikasi (transfer).

### S. Pendaftar Set Interview
- Siswa yang tagihannya lunas masuk ke tab ini.
- **Aksi:** "Tambah Jadwal Interview". Mengelompokkan pendaftar ke dalam **Kelompok Interview** (diisi jadwal, penguji/pegawai yang bertugas).

### T. Manajemen Interview & Penilaian (Sesuai Kebutuhan Client)
- Penguji yang *login* dapat melihat **Kelompok Interview** yang ditugaskan kepadanya.
- Di dalam kelompok, terdapat daftar pendaftar dengan input nilai secara langsung di dalam tabel (seperti Spreadsheet) agar mudah.
- **Kategori Penilaian Akademik (Contoh mengacu pada dokumen Excel):**
  - **Tes Menulis (100%):** Menulis Latin (10%), Menulis Arab (25%), Imla/Dikte (35%), Khat (20%), Kerapian (10%).
  - **Tes Membaca Kitab (100%):** Kelancaran (20%), Harakat (25%), Nahwu (25%), Terjemah (20%), Adab (10%).
  - **Tes Hafalan (100%):** Kelancaran (30%), Tajwid (30%), Makhraj (20%), Adab (20%).
  - **Tes Wawancara / Interview:** Penilaian sesuai formulir. Kriteria wawancara dinilai melalui form terpisah di modal.

### U. Pengumuman & Keberangkatan (Rombongan)
- Setelah nilai dikunci, sistem menentukan kelulusan. Pendaftar dapat men-download **Surat Hasil Tes Sementara** (dapat di-generate dari sistem menggunakan *KOP Surat Client*).
- Jika Lulus, pendaftar dialihkan ke menu **Rombongan** di dashboard-nya untuk mendaftar keberangkatan bersama atau lapor mandiri.

### V. Kedatangan & Tes Kesehatan
- Saat pendaftar tiba di pondok Dalwa, admin melakukan **Check-in**.
- Pendaftar wajib mengikuti tes kesehatan (narkotika, penyakit menular).
- Jika gagal = Tidak jadi santri (Dibatalkan).
- Jika lulus = Diterima Penuh, Sinkronisasi Data. "Nama Pondok", "Asrama", dan "Nomor Kamar" mulai diisi.

---

## 6. Pengaturan Sistem (Settings)
- Mengatur Pola/Format **Nomor Pendaftaran**, **Nomor Invoice** (Contoh: `PSB-[YYYY]-[AUTONUMBER]`).
- Mengatur **Kontak Darurat / Lupa Password** (Nomor WA panitia yang tampil bagi pendaftar yang lupa password).
- Pengaturan Kop Surat (Gambar statis) untuk mencetak kelulusan.

## 7. Sistem Notifikasi (Notifications)
- Bel *Dropdown* Notifikasi di header navbar untuk Admin maupun Pendaftar.
- Muncul ketika tagihan diterbitkan, pembayaran diverifikasi, atau hasil tes diumumkan.

---

## 8. Alur Front-End (Dashboard Pendaftar)
Pendaftar yang masuk ke dashboard akan melihat Sidebar:
1. **Beranda:** Ringkasan status dan pemberitahuan.
2. **Biodata Diri:** Form multi-step (Personal, Orang Tua, Alamat, Pendidikan). Data bergantung pada *FieldConfigs* agar dinamis.
3. **Dokumen:** Form upload dokumen syarat.
4. **Keuangan:** Menampilkan daftar Invoice, Sisa Tagihan, dan Tombol Bayar (Upload Bukti Transfer).
5. **Ujian & Hasil:** Menampilkan jadwal wawancara, link surat kelulusan (jika terbit).
6. **Keberangkatan:** Pemesanan Rombongan (aktif jika lulus seleksi).

---
*(Dokumen PRD ini menggantikan PRD sebelumnya dan menjadi acuan utama pengembangan untuk arsitektur UI dan logika bisnis tahap selanjutnya)*
