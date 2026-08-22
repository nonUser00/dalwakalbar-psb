---
trigger: always_on
---

# PSB (Penerimaan Santri Baru) Core Rules & Guidelines

Berdasarkan PRD versi 1.0, berikut adalah aturan inti (core rules) yang WAJIB diikuti oleh AI (Antigravity) selama proses development:

## 1. Prinsip Utama (Configuration-Driven)
- **Dinamis & Tidak Ada Hardcode:** Sistem PSB bersifat dinamis. Data jenjang, program, gelombang, biaya, dokumen, kategori penilaian, dan status TIDAK BOLEH di-hardcode di frontend (Vue) maupun backend (controller logic) kecuali route, keamanan, atau permission key.
- **Backend as Source of Truth:** Frontend (Vue/Inertia) hanya membaca konfigurasi (business rules) dari backend. Validasi akhir wajib dilakukan di backend.

## 2. Struktur Database & Primary Key
- **Single Source Migration:** JANGAN membuat file migrasi baru dengan prefix `add_`, `edit_`, `alter_`, dll. untuk memperbarui struktur database. Jika ada penambahan, perubahan, atau penghapusan kolom, **wajib** mengedit langsung file migrasi `create_[table_name]_table` yang sudah ada, kemudian jalankan `php artisan migrate:fresh --seed` untuk mengaplikasikan perubahannya. Seluruh relasi (foreign key) juga idealnya diletakkan pada file pembuatan tabel jika urutan tabelnya memungkinkan.
- **UUID:** Semua tabel entitas utama (User, Pegawai, Pendaftar, Periode, Dokumen, Invoice, dll.) WAJIB menggunakan `UUID` sebagai primary key dan foreign key. Nomor pendaftaran/invoice yang dibaca manusia disimpan pada field terpisah dan digenerate menggunakan pola (pattern) yang dinamis.
- **Unique Nullable:** Field yang bersifat `unique` tapi boleh kosong (misal NIK atau nomor ijazah) harus disimpan sebagai `NULL` (empty string harus dikonversi ke NULL sebelum insert/update).
- **Soft Deletes:** Data master yang sudah berelasi dengan data transaksi tidak boleh di-hard delete (gunakan soft deletes atau status inactive).

## 3. Autentikasi & Keamanan (Spatie Permission)
- **Pemisahan Akses:** Ada dua portal login: Admin/Pegawai (`/admin/login`) dan Pendaftar (`/psb/login`).
- **Role & Permission:** Gunakan `spatie/laravel-permission`. Format permission: `module.action` (contoh: `pendaftar.view`, `interview.score`). Semua controller backend WAJIB melakukan validasi permission sebelum mengeksekusi action. Jangan hanya menyembunyikan menu di frontend.
- **Status Locking:** Record yang sudah dikunci (seperti pendaftaran yang sudah disubmit, atau nilai yang di-finalize) tidak boleh diedit lagi oleh pendaftar. Harus menggunakan permission khusus admin (koreksi) untuk mengubahnya, dan dicatat di Activity Log.
- **File Security:** Dokumen yang diunggah pendaftar disimpan di `private storage`. Endpoint download harus memvalidasi kepemilikan dan permission.

## 4. Teknologi & Arsitektur
- **Backend:** Laravel 13, PHP 8.3, Laravel Inertia, Laravel Wayfinder (wajib digunakan untuk komunikasi Vue ke Backend API).
- **Frontend:** Vue 3 + Inertia.js. Wajib Component-based dan Responsive (Admin: Desktop first, Pendaftar: Mobile first).
- **Transactional:** Action penting (generate nomor, submit pendaftaran, finalize score, booking, sinkronisasi) WAJIB dibungkus dengan Database Transaction (`DB::transaction`).


## 5. Data Seeder
- **Pengelompokan Folder:** Semua Data Seeder WAJIB dikelompokkan ke dalam folder masing-masing sesuai konteks/entitasnya. Contoh: `database/seeders/Pegawai/PegawaiSeeder.php`, `database/seeders/Pendaftar/PendaftarSeeder.php`. Hal ini agar struktur seeder tetap rapi ketika sistem membesar.

## 6. Standar Kode & Frontend
- **Kebersihan Kode (Lint & Format):** Semua kode frontend (Vue, Tailwind, TypeScript) WAJIB mematuhi aturan linter dan formatter yang ada. Pastikan tidak ada lint error (misalnya variabel yang dideklarasikan tapi tidak digunakan) dan kode selalu rapi sebelum menyelesaikan tugas.
- **Konsep UI Component:** Selalu gunakan pendekatan UI component base. Hindari penulisan styling dan struktur yang berulang, gunakan komponen UI terpusat dan pergunakan properties (props) atau slot untuk variasi.
- **Pencegahan State Leaking (Form & Modal):** Saat menggunakan Inertia `useForm` pada form yang ada di dalam Modal, slide-over, atau komponen yang sering di-mount ulang/direuse (edit lalu tambah data), pastikan untuk SELALU memanggil `form.reset()` dan `form.clearErrors()` ketika modal ditutup atau dibuka. Hal ini mencegah error message dan data lama (state leak) tertinggal atau muncul kembali di form yang baru.
- **Single Root Element (Inertia Persistent Layout):** Semua file halaman komponen (di dalam `Pages/**`) WAJIB hanya memiliki SATU elemen root utama di dalam `<template>` (seperti `<div class="w-full">`). Jangan membuat lebih dari satu elemen (termasuk tag `<Head>`). Letakkan `<Head>` di dalam div utama tersebut. Hal ini sangat krusial untuk mencegah Inertia me-reset (unmount/remount) *Persistent Layout* (seperti `AdminLayout`) saat berpindah halaman yang menyebabkan efek berkedip/refresh.
- **Penggunaan Ikon SVG (Hindari Emoji/Emoticon):** DILARANG menggunakan emoji atau emoticon (seperti 👤, 🔄, ⏳, 🎓, 💵, 🏛️, ❌, ✅) pada elemen UI, badge, tombol, maupun header tabel. WAJIB menggunakan ikon SVG murni (Heroicons / inline SVG) dengan styling warna Tailwind yang sesuai agar tampilan profesional, konsisten, dan berkualitas tinggi.