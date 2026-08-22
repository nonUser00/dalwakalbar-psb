
---

### 1. Daftar Status Pendaftaran Utama (`PendaftarStatus`)

Status siklus hidup calon santri dari pendaftaran hingga aktif mondok:

| Kode Status | Label Status | Penjelasan |
| :--- | :--- | :--- |
| `DRAFT` | **Draft** | Pendaftar sedang mengisi formulir biodata (belum disubmit). |
| `SUBMITTED` | **Submitted** | Formulir biodata telah lengkap dan disubmit oleh pendaftar. |
| `TAGIHAN` | **Tagihan** | Tagihan pendaftaran telah diterbitkan & menunggu pembayaran. |
| `INTERVIEW` | **Interview** | Pembayaran lunas, pendaftar siap/sedang mengikuti tahap ujian seleksi & wawancara. |
| `LULUS` | **Lulus** | Dinyatakan lulus seleksi dan berhak melanjutkan ke proses registrasi ulang. |
| `TIDAK_LULUS` | **Tidak Lulus** | Calon santri tidak memenuhi kriteria kelulusan seleksi. |
| `KEDATANGAN` | **Kedatangan** | Calon santri telah registrasi ulang dan masuk tahap persiapan keberangkatan/kedatangan ke pondok. |
| `AKTIF` | **Santri Aktif** | Santri telah tiba di pondok dan resmi berstatus santri aktif. |
| `DITOLAK` | **Ditolak** | Pendaftaran dibatalkan atau ditolak oleh panitia/admin. |

---

### 2. Status Penjadwalan Interview (`StatusInterview`)

Status penempatan santri ke dalam kelompok ujian:

| Kode Status | Label Status | Penjelasan |
| :--- | :--- | :--- |
| `belum_dijadwalkan` | **Belum Dijadwalkan** | Santri berstatus `INTERVIEW` namun belum dimasukkan ke kelompok ujian. |
| `terjadwal` | **Sudah Dijadwalkan** | Santri telah memiliki jadwal, ruang ujian, dan kelompok wawancara. |
| `selesai` | **Selesai Interview** | Santri telah menyelesaikan seluruh proses wawancara dan tes. |

---

### 3. Status Kelompok Ujian & Penilaian Kelompok (`KelompokUjian`)

Status pada level rombel/kelompok ujian:

- **Status Pelaksanaan Kelompok (`StatusKelompokUjian`):**
  - `scheduled` (**Terjadwal**)
  - `in_progress` (**Sedang Berlangsung**)
  - `completed` (**Selesai**)
  - `cancelled` (**Dibatalkan**)

- **Status Progres Penilaian Kelompok (Tabel Admin):**
  - **Belum Dinilai:** Belum ada nilai atau hasil wawancara yang diinput.
  - **Sedang Dinilai:** Sebagian santri/komponen tes sudah dinilai, namun belum seluruhnya selesai.
  - **Selesai:** Seluruh santri dalam kelompok telah selesai dinilai dan diwawancara.
  - **Terkunci:** Nilai ujian telah difinalisasi dan dikunci (*locked*) oleh koordinator/admin.

---

### 4. Status & Kategori Hasil Wawancara

Hasil rekomendasi kelayakan dari penguji wawancara:

| Kode | Kategori Hasil | Penjelasan |
| :---: | :--- | :--- |
| **A** | **Memenuhi Syarat** | Calon santri layak diterima langsung tanpa syarat khusus. |
| **C** | **Diterima Bersyarat** | Calon santri diterima dengan catatan atau syarat tertentu (misal: kelas pembinaan). |
| **D** | **Tidak Memenuhi Syarat** | Calon santri tidak direkomendasikan untuk diterima. |

---

### 5. Predikat Nilai Tes Akademik (Membaca, Menulis, Hafalan)

Predikat yang digenerate otomatis berdasarkan rentang skor penilaian aspek (0 – 100):

| Rentang Nilai | Predikat | Keterangan |
| :---: | :--- | :--- |
| **86 – 100** | **BAIK SEKALI** | Memenuhi standar keilmuan secara sempurna |
| **71 – 85** | **BAIK** | Memenuhi kriteria standar dengan baik |
| **56 – 70** | **CUKUP** | Memerlukan pendampingan lebih lanjut |
| **< 56** | **KURANG** | Belum memenuhi kriteria standar |

---

### 6. Pilihan Opsi Penentuan Kelas Pondok

Hasil penempatan jenjang kelas kepesantrenan santri:

1. **Kelas Persiapan (I'dadi):**
   - `I'dadi 1`
   - `I'dadi 2`
2. **Tingkat Dasar (Ibtidaiyah):**
   - `Ibtidaiyah 1`, `Ibtidaiyah 2`, `Ibtidaiyah 3`, `Ibtidaiyah 4`
3. **Tingkat Menengah (Tsanawiyah):**
   - `Tsanawiyah 1`, `Tsanawiyah 2`, `Tsanawiyah 3`

---

### 7. Status Keputusan Kelulusan Final (`StatusKelulusan`)

| Kode Status | Label Status | Penjelasan |
| :--- | :--- | :--- |
| `pending` | **Belum Ditentukan** | Menunggu sidang pleno / verifikasi akhir panitia. |
| `lulus` | **Lulus** | Diterima resmi sebagai santri baru. |
| `tidak_lulus` | **Tidak Lulus** | Tidak diterima di periode ini. |
| `cadangan` | **Cadangan** | Menunggu ketersediaan kuota jika ada peserta lulus yang mengundurkan diri. |
| `pertimbangan` | **Dalam Pertimbangan** | Memerlukan evaluasi khusus dari pimpinan pondok. |