<?php

use App\Http\Controllers\Admin\Akademik\DokumenController;
// Pendaftar
use App\Http\Controllers\Admin\Akademik\ProgramPendidikanController;
use App\Http\Controllers\Admin\Akademik\TahunAkademikController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\Keuangan\MasterKeuanganController;
use App\Http\Controllers\Admin\Keuangan\VirtualAccountController;
use App\Http\Controllers\Admin\Master\PekerjaanOrtuController;
use App\Http\Controllers\Admin\Master\PendidikanOrtuController;
use App\Http\Controllers\Admin\Master\PendidikanPendaftarController;
use App\Http\Controllers\Admin\Master\PenghasilanOrtuController;
use App\Http\Controllers\Admin\Master\UkuranBajuController;
use App\Http\Controllers\Admin\MasterData\MasterReferensiController;
use App\Http\Controllers\Admin\Pendaftar\DraftPendaftarController;
use App\Http\Controllers\Admin\Pendaftar\InterviewPendaftarPageController;
use App\Http\Controllers\Admin\Pendaftar\KedatanganController;
// Keuangan
use App\Http\Controllers\Admin\Pendaftar\KelompokInterviewController;
use App\Http\Controllers\Admin\Pendaftar\MasterPenilaianController;
use App\Http\Controllers\Admin\Pendaftar\PendaftarController;
use App\Http\Controllers\Admin\Pendaftar\PengumumanPendaftarPageController;
use App\Http\Controllers\Admin\Pendaftar\PenilaianPendaftarPageController;
// Akademik
use App\Http\Controllers\Admin\Pendaftar\RombonganController;
use App\Http\Controllers\Admin\Pendaftar\SinkronisasiController;
use App\Http\Controllers\Admin\Pendaftar\SubmitPendaftarController;
use App\Http\Controllers\Admin\Pendaftar\TagihanPendaftarController;
use App\Http\Controllers\Admin\Pendaftar\TagihanPendaftarPageController;
use App\Http\Controllers\Admin\Pengaturan\LogController;
// Pengaturan
use App\Http\Controllers\Admin\Pengaturan\PegawaiController;
use App\Http\Controllers\Admin\Pengaturan\RolePermissionController;
use App\Http\Controllers\Admin\Pengaturan\SettingController;
use App\Http\Controllers\Admin\ProfileController;
// PSB (Calon Santri)
use App\Http\Controllers\Api\IndonesiaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Psb\AuthController as PsbAuthController;
use App\Http\Controllers\Psb\BiodataController;
use App\Http\Controllers\Psb\DashboardController as PsbDashboardController;
use App\Http\Controllers\Psb\KeberangkatanController;
use App\Http\Controllers\Psb\KeuanganController as PsbKeuanganController;
use App\Http\Controllers\Psb\ProfileController as PsbProfileController;
use App\Http\Controllers\Psb\RegisterController;
use App\Http\Controllers\Psb\UjianController as PsbUjianController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// ==========================================
// AUTH ADMIN
// ==========================================
Route::middleware('guest:web,pendaftar')->group(function () {
    Route::get('admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('admin/login', [AuthController::class, 'login']);
});
Route::post('admin/logout', [AuthController::class, 'logout'])->name('admin.logout')->middleware('auth');

// ==========================================
// AUTH PSB (PENDAFTAR)
// ==========================================
Route::middleware('guest:web,pendaftar')->group(function () {
    Route::get('psb/login', [PsbAuthController::class, 'showLoginForm'])->name('psb.login');
    Route::post('psb/login', [PsbAuthController::class, 'login']);
    Route::get('psb/forgot-password', [PsbAuthController::class, 'showForgotPasswordForm'])->name('psb.password.request');
    Route::get('psb/register', [RegisterController::class, 'showRegistrationForm'])->name('psb.register');
    Route::post('psb/register', [RegisterController::class, 'register']);
});
Route::get('psb/register/success', [RegisterController::class, 'showSuccessPage'])->name('psb.register.success')->middleware('auth:pendaftar');
Route::post('psb/logout', [PsbAuthController::class, 'logout'])->name('psb.logout')->middleware('auth:pendaftar');

// ==========================================
// ADMIN PANEL ROUTES
// ==========================================
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {

    // ROOT REDIRECT TO DASHBOARD
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // DASHBOARD
    Route::get('/dashboard', function () {
        return inertia('Admin/Dashboard/Index');
    })->name('dashboard');

    // PROFIL SAYA
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // 1. MANAJEMEN PENDAFTAR
    Route::prefix('pendaftar')->name('pendaftar.')->group(function () {
        // 1.1 Pendaftar Draft
        Route::prefix('draft')->name('draft.')->group(function () {
            Route::get('/', [DraftPendaftarController::class, 'index'])->name('index');
            Route::get('/export', [DraftPendaftarController::class, 'export'])->name('export');
            Route::post('/bulk-destroy', [DraftPendaftarController::class, 'bulkDestroy'])->name('bulk_destroy');
            Route::post('/{pendaftar}/reset-password', [DraftPendaftarController::class, 'resetPassword'])->name('reset_password');
            Route::delete('/{pendaftar}', [DraftPendaftarController::class, 'destroy'])->name('destroy');
        });

        // 1.2 Pendaftar Submit
        Route::prefix('submit')->name('submit.')->group(function () {
            Route::get('/', [SubmitPendaftarController::class, 'index'])->name('index');
            Route::get('/export', [SubmitPendaftarController::class, 'export'])->name('export');
            Route::post('/bulk-destroy', [SubmitPendaftarController::class, 'bulkDestroy'])->name('bulk_destroy');
            Route::post('/bulk-verify', [SubmitPendaftarController::class, 'bulkVerify'])->name('bulk_verify');
            Route::post('/{pendaftar}/verify', [SubmitPendaftarController::class, 'verify'])->name('verify');
            Route::post('/{pendaftar}/reset-password', [SubmitPendaftarController::class, 'resetPassword'])->name('reset_password');
            Route::delete('/{pendaftar}', [SubmitPendaftarController::class, 'destroy'])->name('destroy');
        });

        // 1.3 Pendaftar Tagihan
        Route::prefix('tagihan')->name('tagihan.')->group(function () {
            Route::get('/', [TagihanPendaftarPageController::class, 'index'])->name('index');
            Route::get('/export', [TagihanPendaftarPageController::class, 'export'])->name('export');
            Route::get('/buat', [TagihanPendaftarPageController::class, 'create'])->name('create');
            Route::get('/detail/{pendaftar}', [TagihanPendaftarPageController::class, 'showDetail'])->name('show_detail');
            Route::post('/create-bill', [TagihanPendaftarPageController::class, 'createBill'])->name('create_bill');
            Route::delete('/{tagihan}/destroy-bill', [TagihanPendaftarPageController::class, 'destroyBill'])->name('destroy_bill');
            Route::post('/{tagihan}/add-payment', [TagihanPendaftarPageController::class, 'addPayment'])->name('add_payment');
            Route::put('/payment/{pembayaran}', [TagihanPendaftarPageController::class, 'editPayment'])->name('edit_payment');
            Route::delete('/payment/{pembayaran}', [TagihanPendaftarPageController::class, 'deletePayment'])->name('delete_payment');
            Route::post('/payment/bulk-delete', [TagihanPendaftarPageController::class, 'bulkDeletePayments'])->name('bulk_delete_payments');
            Route::post('/payment/{pembayaran}/verify', [TagihanPendaftarPageController::class, 'verifyPayment'])->name('verify_payment');
            Route::post('/bulk-destroy', [TagihanPendaftarPageController::class, 'bulkDestroy'])->name('bulk_destroy');
            Route::post('/{pendaftar}/reset-password', [TagihanPendaftarPageController::class, 'resetPassword'])->name('reset_password');
            Route::delete('/{pendaftar}', [TagihanPendaftarPageController::class, 'destroy'])->name('destroy');
            Route::get('/{tagihan}/show', [TagihanPendaftarController::class, 'show'])->name('show');
        });

        // 1.4 Pendaftar Set Interview
        Route::prefix('set-interview')->name('set_interview.')->group(function () {
            Route::get('/', [InterviewPendaftarPageController::class, 'index'])->name('index');
            Route::get('/export', [InterviewPendaftarPageController::class, 'export'])->name('export');
            Route::get('/create', [InterviewPendaftarPageController::class, 'create'])->name('create');
            Route::post('/schedule', [InterviewPendaftarPageController::class, 'schedule'])->name('schedule');
            Route::post('/{pendaftar}/remove-schedule', [InterviewPendaftarPageController::class, 'removeSchedule'])->name('remove_schedule');
            Route::post('/bulk-destroy', [InterviewPendaftarPageController::class, 'bulkDestroy'])->name('bulk_destroy');
            Route::post('/{pendaftar}/reset-password', [InterviewPendaftarPageController::class, 'resetPassword'])->name('reset_password');
            Route::delete('/{pendaftar}', [InterviewPendaftarPageController::class, 'destroy'])->name('destroy');
        });

        // Redirects for backwards compatibility
        Route::redirect('interview', '/admin/pendaftar/set-interview');
        Route::redirect('interview/create', '/admin/pendaftar/set-interview/create');
        Route::redirect('interview/export', '/admin/pendaftar/set-interview/export');

        Route::resource('kelompok', KelompokInterviewController::class)->except(['create', 'show', 'edit']);
        Route::resource('master-penilaian', MasterPenilaianController::class)->except(['create', 'show', 'edit']);

        // 1.5 Pendaftar Penilaian Interview
        Route::prefix('penilaian-interview')->name('penilaian_interview.')->group(function () {
            Route::get('/', [PenilaianPendaftarPageController::class, 'index'])->name('index');
            Route::get('/export', [PenilaianPendaftarPageController::class, 'export'])->name('export');
            Route::get('/kelompok/{kelompokUjian}', [PenilaianPendaftarPageController::class, 'showKelompok'])->name('show_kelompok');
            Route::get('/kelompok/{kelompokUjian}/spreadsheet', [PenilaianPendaftarPageController::class, 'spreadsheet'])->name('spreadsheet');
            Route::get('/{pendaftar}/cetak-surat-hasil', [PenilaianPendaftarPageController::class, 'cetakSuratHasil'])->name('cetak_surat');
            Route::post('/score', [PenilaianPendaftarPageController::class, 'storeScore'])->name('store_score');

            // Wawancara Dedicated Page
            Route::get('/kelompok/{kelompokUjian}/wawancara/{pendaftar}', [PenilaianPendaftarPageController::class, 'showWawancaraPage'])->name('wawancara.show');
            Route::post('/wawancara', [PenilaianPendaftarPageController::class, 'storeInterviewNote'])->name('store_wawancara');
            Route::post('/penentuan-kelas', [PenilaianPendaftarPageController::class, 'storePenentuanKelas'])->name('store_penentuan_kelas');
            Route::post('/kelulusan', [PenilaianPendaftarPageController::class, 'storeKelulusan'])->name('store_kelulusan');

            // Lembar Tes Dedicated Pages (Tes Membaca, Tes Menulis, Tes Hafalan)
            Route::get('/kelompok/{kelompokUjian}/tes/{kategoriSlug}', [PenilaianPendaftarPageController::class, 'showLembarTes'])->name('lembar_tes.show');
            Route::post('/kelompok/{kelompokUjian}/tes/{kategoriSlug}/save-single', [PenilaianPendaftarPageController::class, 'saveSingleScore'])->name('lembar_tes.save_single');
            Route::post('/kelompok/{kelompokUjian}/tes/{kategoriSlug}/save-batch', [PenilaianPendaftarPageController::class, 'saveBatchScore'])->name('lembar_tes.save_batch');

            Route::post('/kelompok/{kelompokUjian}/lock-all', [PenilaianPendaftarPageController::class, 'lockKelompok'])->name('lock_kelompok');
            Route::post('/kelompok/{kelompokUjian}/unlock-all', [PenilaianPendaftarPageController::class, 'unlockKelompok'])->name('unlock_kelompok');
            Route::post('/{pendaftar}/finalize', [PenilaianPendaftarPageController::class, 'finalize'])->name('finalize');
            Route::post('/bulk-finalize', [PenilaianPendaftarPageController::class, 'bulkFinalize'])->name('bulk_finalize');
            Route::post('/{pendaftar}/unlock', [PenilaianPendaftarPageController::class, 'unlock'])->name('unlock');
            Route::post('/bulk-destroy', [PenilaianPendaftarPageController::class, 'bulkDestroy'])->name('bulk_destroy');
            Route::post('/{pendaftar}/reset-password', [PenilaianPendaftarPageController::class, 'resetPassword'])->name('reset_password');
            Route::get('/kelompok/{kelompokUjian}/edit', [PenilaianPendaftarPageController::class, 'editKelompok'])->name('edit_kelompok');
            Route::put('/kelompok/{kelompokUjian}', [PenilaianPendaftarPageController::class, 'updateKelompok'])->name('update_kelompok');
            Route::delete('/kelompok/{kelompokUjian}', [PenilaianPendaftarPageController::class, 'destroyKelompok'])->name('destroy_kelompok');
            Route::delete('/{pendaftar}', [PenilaianPendaftarPageController::class, 'destroy'])->name('destroy');
        });

        // Redirects for backwards compatibility
        Route::redirect('penilaian', '/admin/pendaftar/penilaian-interview');
        Route::redirect('penilaian/export', '/admin/pendaftar/penilaian-interview/export');

        // 1.6 Pendaftar Pengumuman Kelulusan
        Route::prefix('pengunguman')->name('pengumuman.')->group(function () {
            Route::get('/', [PengumumanPendaftarPageController::class, 'index'])->name('index');
            Route::get('/export', [PengumumanPendaftarPageController::class, 'export'])->name('export');
            Route::post('/{pendaftar}/decide', [PengumumanPendaftarPageController::class, 'decide'])->name('decide');
            Route::post('/bulk-decide', [PengumumanPendaftarPageController::class, 'bulkDecide'])->name('bulk_decide');
            Route::post('/{pendaftar}/reinterview', [PengumumanPendaftarPageController::class, 'reinterview'])->name('reinterview');
            Route::post('/bulk-destroy', [PengumumanPendaftarPageController::class, 'bulkDestroy'])->name('bulk_destroy');
            Route::post('/{pendaftar}/reset-password', [PengumumanPendaftarPageController::class, 'resetPassword'])->name('reset_password');
            Route::delete('/{pendaftar}', [PengumumanPendaftarPageController::class, 'destroy'])->name('destroy');
        });

        // Pendaftar General / Dynamic Routes (Must be after all specific sub-routes)
        Route::get('/', [PendaftarController::class, 'index'])->name('index');
        Route::get('/export', [PendaftarController::class, 'export'])->name('export');
        Route::post('/bulk-tagihan', [PendaftarController::class, 'bulkCreateTagihan'])->name('bulk_create_tagihan');
        Route::get('/{pendaftar}', [PendaftarController::class, 'show'])->name('show');
        Route::get('/{pendaftar}/cetak-kartu', [PendaftarController::class, 'cetakKartu'])->name('cetak_kartu');
        Route::post('/{pendaftar}/reset-password', [PendaftarController::class, 'resetPassword'])->name('reset_password');
        Route::post('/{pendaftar}/verify', [PendaftarController::class, 'verify'])->name('verify');
        Route::delete('/{pendaftar}', [PendaftarController::class, 'destroy'])->name('destroy');

        // Aliases for compatibility
        Route::get('pengumuman', [PengumumanPendaftarPageController::class, 'index'])->name('pengumuman.alias');
        Route::get('hasil', [PengumumanPendaftarPageController::class, 'index'])->name('hasil.index');

        // Pengumuman, Kedatangan & Rombongan
        Route::resource('rombongan', RombonganController::class)->except(['create', 'show', 'edit']);
        Route::get('kedatangan', [KedatanganController::class, 'index'])->name('kedatangan.index');
        Route::post('kedatangan/{pendaftar}/kesehatan', [KedatanganController::class, 'updateKesehatan'])->name('kedatangan.kesehatan');

        Route::get('sinkronisasi', [SinkronisasiController::class, 'index'])->name('sinkronisasi.index');
        Route::post('sinkronisasi/{pendaftar}', [SinkronisasiController::class, 'sinkronisasi'])->name('sinkronisasi.store');
    });

    // 2. KEUANGAN & BIAYA
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        // Master Keuangan (Bank & 4 Jenis Tagihan/Biaya)
        Route::get('/bank', [MasterKeuanganController::class, 'bankIndex'])->name('bank.index');
        Route::get('/tagihan-pendaftaran', [MasterKeuanganController::class, 'tagihanPendaftaranIndex'])->name('tagihan-pendaftaran.index');
        Route::get('/tagihan-rombongan', [MasterKeuanganController::class, 'tagihanRombonganIndex'])->name('tagihan-rombongan.index');
        Route::get('/tagihan-interview', [MasterKeuanganController::class, 'tagihanInterviewIndex'])->name('tagihan-interview.index');
        Route::get('/tagihan-biasa', [MasterKeuanganController::class, 'tagihanBiasaIndex'])->name('tagihan-biasa.index');
        Route::get('/biaya', [MasterKeuanganController::class, 'biayaIndex'])->name('biaya.index');
        Route::post('/master/{model}', [MasterKeuanganController::class, 'store'])->name('master.store');
        Route::match(['put', 'post'], '/master/{model}/{id}', [MasterKeuanganController::class, 'update'])->name('master.update');
        Route::delete('/master/{model}/{id}', [MasterKeuanganController::class, 'destroy'])->name('master.destroy');

        // Virtual Account
        Route::get('va/template', [VirtualAccountController::class, 'downloadTemplate'])->name('va.template');
        Route::match(['get', 'post'], 'va/export', [VirtualAccountController::class, 'export'])->name('va.export');
        Route::get('va/import', [VirtualAccountController::class, 'importPage'])->name('va.import-page');
        Route::post('va/import', [VirtualAccountController::class, 'importSubmit'])->name('va.import-submit');
        Route::resource('va', VirtualAccountController::class)->except(['create', 'show', 'edit']);
    });

    // 3. AKADEMIK & PENGATURAN PENDAFTARAN
    Route::prefix('akademik')->name('akademik.')->group(function () {
        Route::prefix('program')->name('program.')->group(function () {
            Route::get('/', [ProgramPendidikanController::class, 'index'])->name('index');
            Route::post('/{model}', [ProgramPendidikanController::class, 'store'])->name('store');
            Route::put('/{model}/{id}', [ProgramPendidikanController::class, 'update'])->name('update');
            Route::delete('/{model}/{id}', [ProgramPendidikanController::class, 'destroy'])->name('destroy');
        });
        Route::resource('dokumen', DokumenController::class)->except(['create', 'show', 'edit']);

        Route::prefix('tahun-akademik')->name('tahun_akademik.')->group(function () {
            Route::get('/', [TahunAkademikController::class, 'index'])->name('index');
            Route::get('/periode/create', [TahunAkademikController::class, 'createPeriode'])->name('periode.create');
            Route::get('/periode/{id}/edit', [TahunAkademikController::class, 'editPeriode'])->name('periode.edit');
            Route::post('/{model}', [TahunAkademikController::class, 'store'])->name('store');
            Route::put('/{model}/{id}', [TahunAkademikController::class, 'update'])->name('update');
            Route::delete('/{model}/{id}', [TahunAkademikController::class, 'destroy'])->name('destroy');
        });
    });

    // 4. MASTER DATA
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('referensi', [MasterReferensiController::class, 'index'])->name('referensi.index');
        Route::post('referensi/{model}', [MasterReferensiController::class, 'store'])->name('referensi.store');
        Route::put('referensi/{model}/{id}', [MasterReferensiController::class, 'update'])->name('referensi.update');
        Route::delete('referensi/{model}/{id}', [MasterReferensiController::class, 'destroy'])->name('referensi.destroy');

        // Pendidikan Pendaftar (Pendidikan Sebelumnya)
        Route::prefix('pendidikan-sebelumnya')->name('pendidikan-sebelumnya.')->group(function () {
            Route::get('/', [PendidikanPendaftarController::class, 'index'])->name('index');
            Route::post('/', [PendidikanPendaftarController::class, 'store'])->name('store');
            Route::put('/{pendidikanPendaftar}', [PendidikanPendaftarController::class, 'update'])->name('update');
            Route::delete('/{pendidikanPendaftar}', [PendidikanPendaftarController::class, 'destroy'])->name('destroy');

            Route::post('/{pendidikanPendaftar}/tingkat', [PendidikanPendaftarController::class, 'storeTingkat'])->name('tingkat.store');
            Route::put('/tingkat/{tingkat}', [PendidikanPendaftarController::class, 'updateTingkat'])->name('tingkat.update');
            Route::delete('/tingkat/{tingkat}', [PendidikanPendaftarController::class, 'destroyTingkat'])->name('tingkat.destroy');
        });

        // Pekerjaan Orang Tua
        Route::prefix('pekerjaan-orang-tua')->name('pekerjaan-orang-tua.')->group(function () {
            Route::get('/', [PekerjaanOrtuController::class, 'index'])->name('index');
            Route::post('/', [PekerjaanOrtuController::class, 'store'])->name('store');
            Route::put('/{pekerjaanOrtu}', [PekerjaanOrtuController::class, 'update'])->name('update');
            Route::delete('/{pekerjaanOrtu}', [PekerjaanOrtuController::class, 'destroy'])->name('destroy');
        });

        // Penghasilan Orang Tua
        Route::prefix('penghasilan-orang-tua')->name('penghasilan-orang-tua.')->group(function () {
            Route::get('/', [PenghasilanOrtuController::class, 'index'])->name('index');
            Route::post('/', [PenghasilanOrtuController::class, 'store'])->name('store');
            Route::put('/{penghasilanOrtu}', [PenghasilanOrtuController::class, 'update'])->name('update');
            Route::delete('/{penghasilanOrtu}', [PenghasilanOrtuController::class, 'destroy'])->name('destroy');
        });

        // Pendidikan Terakhir Orang Tua
        Route::prefix('pendidikan-terakhir-orang-tua')->name('pendidikan-terakhir-orang-tua.')->group(function () {
            Route::get('/', [PendidikanOrtuController::class, 'index'])->name('index');
            Route::post('/', [PendidikanOrtuController::class, 'store'])->name('store');
            Route::put('/{pendidikanOrtu}', [PendidikanOrtuController::class, 'update'])->name('update');
            Route::delete('/{pendidikanOrtu}', [PendidikanOrtuController::class, 'destroy'])->name('destroy');
        });

        // Ukuran Baju
        Route::prefix('ukuran-baju')->name('ukuran-baju.')->group(function () {
            Route::get('/', [UkuranBajuController::class, 'index'])->name('index');
            Route::post('/', [UkuranBajuController::class, 'store'])->name('store');
            Route::put('/{ukuranBaju}', [UkuranBajuController::class, 'update'])->name('update');
            Route::delete('/{ukuranBaju}', [UkuranBajuController::class, 'destroy'])->name('destroy');
        });
    });

    // 6. LAPORAN & RIWAYAT
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/{type}', function ($type) {
            return inertia('Admin/Laporan/Index', ['type' => $type]);
        })->name('index');
    });

    // 5. PENGATURAN SISTEM
    Route::prefix('pengaturan')->name('pengaturan.')->group(function () {
        // Pegawai
        Route::prefix('pegawai')->name('pegawai.')->group(function () {
            Route::get('/', [PegawaiController::class, 'index'])->name('index');
            Route::get('/create', [PegawaiController::class, 'create'])->name('create');
            Route::post('/', [PegawaiController::class, 'store'])->name('store');
            Route::get('/{pegawai}/edit', [PegawaiController::class, 'edit'])->name('edit');
            Route::get('/{pegawai}/detail', [PegawaiController::class, 'show'])->name('show');
            Route::put('/{pegawai}', [PegawaiController::class, 'update'])->name('update');
            Route::delete('/{pegawai}', [PegawaiController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [PegawaiController::class, 'bulkDestroy'])->name('bulk-delete');
            Route::post('/{pegawai}/role', [PegawaiController::class, 'updateRole'])->name('update-role');
            Route::post('/{pegawai}/reset-password', [PegawaiController::class, 'resetPassword'])->name('reset-password');
            Route::post('/{pegawai}/toggle-status', [PegawaiController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/import-excel', [PegawaiController::class, 'importPage'])->name('import-page');
            Route::post('/import', [PegawaiController::class, 'import'])->name('import');
            Route::post('/export', [PegawaiController::class, 'export'])->name('export');
            Route::get('/export-template', [PegawaiController::class, 'exportTemplate'])->name('export-template');
        });

        // Role Permission
        Route::prefix('role-permission')->name('role-permission.')->group(function () {
            Route::get('/', [RolePermissionController::class, 'index'])->name('index');
            Route::post('/role', [RolePermissionController::class, 'storeRole'])->name('role.store');
            Route::put('/role/{role}', [RolePermissionController::class, 'updateRole'])->name('role.update');
            Route::delete('/role/{role}', [RolePermissionController::class, 'destroyRole'])->name('role.destroy');
            Route::post('/permission', [RolePermissionController::class, 'storePermission'])->name('permission.store');
            Route::delete('/permission/{permission}', [RolePermissionController::class, 'destroyPermission'])->name('permission.destroy');
        });

        // Log System
        Route::prefix('log')->name('log.')->group(function () {
            Route::get('/', [LogController::class, 'index'])->name('index');
            Route::delete('/{id}', [LogController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [LogController::class, 'bulkDestroy'])->name('bulk-delete');
            Route::post('/clear', [LogController::class, 'clear'])->name('clear');
            Route::post('/export', [LogController::class, 'export'])->name('export');
            Route::get('/{id}', [LogController::class, 'show'])->name('show');
        });

        // Setting / Konfigurasi
        Route::prefix('konfigurasi')->name('konfigurasi.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('/general', [SettingController::class, 'updateGeneral'])->name('general');
            Route::post('/kop-surat', [SettingController::class, 'updateKopSurat'])->name('kop-surat');
            Route::put('/sequence/{sequence}', [SettingController::class, 'updateSequence'])->name('sequence');
        });
    });

    // NOTIFICATIONS
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'getAdminNotifications'])->name('get');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
    });
});

// ==========================================
// PSB (PENDAFTAR) ROUTES
// ==========================================
Route::middleware('auth:pendaftar')->prefix('psb')->name('psb.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('psb.dashboard');
    });
    Route::get('/dashboard', [PsbDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [PsbProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/password', [PsbProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/cetak-kartu', [PsbDashboardController::class, 'cetakKartu'])->name('cetak_kartu');

    // Formulir Pendaftaran (5 Steps: Personal, Ortu, Alamat, Pendidikan, Dokumen)
    Route::get('/biodata', [BiodataController::class, 'index'])->name('biodata.index');
    Route::post('/biodata', [BiodataController::class, 'update'])->name('biodata.update');
    Route::post('/biodata/upload-dokumen', [BiodataController::class, 'uploadDokumen'])->name('biodata.upload_dokumen');
    Route::post('/biodata/submit-final', [BiodataController::class, 'submitFinal'])->name('biodata.submit_final');

    // Redirect legacy route
    Route::get('/dokumen', fn () => redirect()->route('psb.biodata.index', ['step' => 5]))->name('dokumen.index');

    // Administrasi & Keuangan PSB
    Route::get('/keuangan', [PsbKeuanganController::class, 'index'])->name('keuangan.index');
    Route::get('/keuangan/tagihan', [PsbKeuanganController::class, 'tagihan'])->name('keuangan.tagihan');
    Route::get('/keuangan/tagihan/{tagihan}', [PsbKeuanganController::class, 'showTagihan'])->name('keuangan.tagihan.show');
    Route::get('/keuangan/tagihan/{tagihan}/bayar', [PsbKeuanganController::class, 'bayar'])->name('keuangan.bayar_page');
    Route::get('/keuangan/pembayaran/{pembayaran}/edit', [PsbKeuanganController::class, 'editPembayaran'])->name('keuangan.pembayaran.edit');
    Route::post('/keuangan/pembayaran/{pembayaran}/update', [PsbKeuanganController::class, 'updatePembayaran'])->name('keuangan.pembayaran.update');
    Route::delete('/keuangan/pembayaran/{pembayaran}', [PsbKeuanganController::class, 'destroyPembayaran'])->name('keuangan.pembayaran.destroy');
    Route::get('/keuangan/va', [PsbKeuanganController::class, 'virtualAccount'])->name('keuangan.va');
    Route::get('/keuangan/riwayat', [PsbKeuanganController::class, 'riwayat'])->name('keuangan.riwayat');
    Route::get('/keuangan/riwayat/{tagihan}', [PsbKeuanganController::class, 'showRiwayatTagihan'])->name('keuangan.riwayat.show');
    Route::post('/keuangan/bayar', [PsbKeuanganController::class, 'uploadBukti'])->name('keuangan.bayar');

    // Seleksi & Ujian PSB
    Route::get('/ujian', [PsbUjianController::class, 'index'])->name('ujian.index');
    Route::get('/ujian/jadwal', [PsbUjianController::class, 'jadwal'])->name('ujian.jadwal');
    Route::get('/ujian/pengumuman', [PsbUjianController::class, 'pengumuman'])->name('ujian.pengumuman');

    Route::get('/keberangkatan', [KeberangkatanController::class, 'index'])->name('keberangkatan.index');
    Route::post('/keberangkatan', [KeberangkatanController::class, 'store'])->name('keberangkatan.store');

    // Notifications PSB
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'getPsbNotifications'])->name('get');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
    });
});

// ==========================================
// API GLOBAL
// ==========================================
Route::prefix('api/indonesia')->name('api.indonesia.')->group(function () {
    Route::get('provinces', [IndonesiaController::class, 'provinces'])->name('provinces');
    Route::get('cities', [IndonesiaController::class, 'cities'])->name('cities');
    Route::get('districts', [IndonesiaController::class, 'districts'])->name('districts');
    Route::get('villages', [IndonesiaController::class, 'villages'])->name('villages');
});
