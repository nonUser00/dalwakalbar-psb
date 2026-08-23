<?php

namespace Database\Seeders;

use Database\Seeders\Asrama\AsramaPermissionSeeder;
use Database\Seeders\Asrama\RombonganSeeder;
use Database\Seeders\Auth\AuthPermissionSeeder;
use Database\Seeders\Auth\PegawaiSeeder;
use Database\Seeders\Keuangan\BankSeeder;
use Database\Seeders\Keuangan\BiayaSeeder;
use Database\Seeders\Keuangan\KeuanganPermissionSeeder;
use Database\Seeders\Master\CabangSeeder;
use Database\Seeders\Master\DokumenSeeder;
use Database\Seeders\Master\MasterPermissionSeeder;
use Database\Seeders\Master\PekerjaanOrtuSeeder;
use Database\Seeders\Master\PendidikanOrtuSeeder;
use Database\Seeders\Master\PenghasilanOrtuSeeder;
use Database\Seeders\Master\ProgramPendidikanSeeder;
use Database\Seeders\Master\TahunAkademikSeeder;
use Database\Seeders\Master\UkuranBajuSeeder;
use Database\Seeders\Pendaftar\PendaftarPermissionSeeder;
use Database\Seeders\Pendaftar\PendaftarSeeder;
use Database\Seeders\Pendaftar\PendidikanPendaftarSeeder;
use Database\Seeders\Pendaftaran\PeriodeGelombangSeeder;
use Database\Seeders\Setting\PengaturanPermissionSeeder;
use Database\Seeders\Setting\SettingSeeder;
use Database\Seeders\Ujian\MasterPenilaianSeeder;
use Database\Seeders\Ujian\UjianPermissionSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('laravolt:indonesia:seed');

        $this->call([
            // 1. Auth & Pegawai
            AuthPermissionSeeder::class,
            PegawaiSeeder::class,

            // 2. Master Data
            CabangSeeder::class,
            ProgramPendidikanSeeder::class,
            DokumenSeeder::class,
            PekerjaanOrtuSeeder::class,
            PenghasilanOrtuSeeder::class,
            PendidikanOrtuSeeder::class,
            UkuranBajuSeeder::class,
            TahunAkademikSeeder::class,
            MasterPermissionSeeder::class,

            // 3. Setting & Pengaturan
            SettingSeeder::class,
            PengaturanPermissionSeeder::class,

            // 4. Pendaftaran
            PeriodeGelombangSeeder::class,

            // 5. Keuangan Master
            KeuanganPermissionSeeder::class,
            BankSeeder::class,
            BiayaSeeder::class,

            // 6. Ujian Master
            UjianPermissionSeeder::class,
            MasterPenilaianSeeder::class,

            // 7. Pendaftar
            PendidikanPendaftarSeeder::class,
            PendaftarPermissionSeeder::class,
            PendaftarSeeder::class,

            // 8. Asrama
            AsramaPermissionSeeder::class,
            RombonganSeeder::class,
        ]);
    }
}
