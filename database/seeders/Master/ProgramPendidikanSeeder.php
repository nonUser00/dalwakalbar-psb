<?php

namespace Database\Seeders\Master;

use App\Models\Master\Fakultas;
use App\Models\Master\Jenjang;
use App\Models\Master\Jurusan;
use App\Models\Master\Prodi;
use App\Models\Master\Tingkat;
use Illuminate\Database\Seeder;

class ProgramPendidikanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. MTs (Madrasah Tsanawiyah)
        $mts = Jenjang::updateOrCreate(
            ['code' => 'MTs'],
            [
                'name' => 'Madrasah Tsanawiyah',
                'singkatan' => 'MTs',
                'logo_path' => 'image/logos/jenjang/logo-mts.png',
                'gender_allowed' => 'ALL',
                'is_active' => true,
            ]
        );
        foreach (['Kelas 7', 'Kelas 8', 'Kelas 9'] as $t) {
            Tingkat::firstOrCreate(
                ['jenjang_id' => $mts->id, 'name' => $t],
                ['gender_allowed' => 'ALL']
            );
        }

        // 2. MA (Madrasah Aliyah)
        $ma = Jenjang::updateOrCreate(
            ['code' => 'MA'],
            [
                'name' => 'Madrasah Aliyah',
                'singkatan' => 'MA',
                'logo_path' => 'image/logos/jenjang/logo-ma.png',
                'gender_allowed' => 'ALL',
                'is_active' => true,
            ]
        );
        foreach (['Kelas 10', 'Kelas 11', 'Kelas 12'] as $t) {
            Tingkat::firstOrCreate(
                ['jenjang_id' => $ma->id, 'name' => $t],
                ['gender_allowed' => 'ALL']
            );
        }
        $jurusansMA = [
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'gender_allowed' => 'ALL'],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'gender_allowed' => 'ALL'],
            ['code' => 'AGM', 'name' => 'Keagamaan (Al-Ahwal Al-Syakhsiyyah)', 'gender_allowed' => 'ALL'],
        ];
        foreach ($jurusansMA as $j) {
            Jurusan::firstOrCreate(
                ['jenjang_id' => $ma->id, 'code' => $j['code']],
                ['name' => $j['name'], 'gender_allowed' => $j['gender_allowed']]
            );
        }

        // 3. S1 (Strata 1 / Sarjana)
        $s1 = Jenjang::updateOrCreate(
            ['code' => 'S1'],
            [
                'name' => 'Strata 1 (Sarjana)',
                'singkatan' => 'S1',
                'logo_path' => 'image/logos/jenjang/logo-uii dalwa.png',
                'gender_allowed' => 'ALL',
                'is_active' => true,
            ]
        );
        $fakultasS1 = [
            [
                'code' => 'FT',
                'name' => 'Fakultas Tarbiyah',
                'prodis' => [
                    ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'gender_allowed' => 'ALL'],
                    ['code' => 'PBA', 'name' => 'Pendidikan Bahasa Arab', 'gender_allowed' => 'ALL'],
                ],
            ],
            [
                'code' => 'FS',
                'name' => 'Fakultas Syariah',
                'prodis' => [
                    ['code' => 'HKI', 'name' => 'Hukum Keluarga Islam', 'gender_allowed' => 'ALL'],
                    ['code' => 'HES', 'name' => 'Hukum Ekonomi Syariah', 'gender_allowed' => 'ALL'],
                ],
            ],
        ];
        foreach ($fakultasS1 as $f) {
            $fak = Fakultas::firstOrCreate(
                ['jenjang_id' => $s1->id, 'code' => $f['code']],
                ['name' => $f['name']]
            );
            foreach ($f['prodis'] as $p) {
                Prodi::firstOrCreate(
                    ['fakultas_id' => $fak->id, 'code' => $p['code']],
                    ['name' => $p['name'], 'gender_allowed' => $p['gender_allowed']]
                );
            }
        }

        // 4. S2 (Pasca Sarjana / Magister)
        $s2 = Jenjang::updateOrCreate(
            ['code' => 'S2'],
            [
                'name' => 'Pasca Sarjana (Magister)',
                'singkatan' => 'S2',
                'logo_path' => 'image/logos/jenjang/logo-uii dalwa.png',
                'gender_allowed' => 'ALL',
                'is_active' => true,
            ]
        );
        $fakS2 = Fakultas::firstOrCreate(
            ['jenjang_id' => $s2->id, 'code' => 'FPS'],
            ['name' => 'Fakultas Pascasarjana']
        );
        $prodisS2 = [
            ['code' => 'MPAI', 'name' => 'Magister Pendidikan Agama Islam', 'gender_allowed' => 'ALL'],
            ['code' => 'MHI', 'name' => 'Magister Hukum Islam', 'gender_allowed' => 'ALL'],
        ];
        foreach ($prodisS2 as $p) {
            Prodi::firstOrCreate(
                ['fakultas_id' => $fakS2->id, 'code' => $p['code']],
                ['name' => $p['name'], 'gender_allowed' => $p['gender_allowed']]
            );
        }

        // 5. S3 (Doktor)
        $s3 = Jenjang::updateOrCreate(
            ['code' => 'S3'],
            [
                'name' => 'Doktor (S3)',
                'singkatan' => 'S3',
                'logo_path' => 'image/logos/jenjang/logo-uii dalwa.png',
                'gender_allowed' => 'ALL',
                'is_active' => true,
            ]
        );
        $fakS3 = Fakultas::firstOrCreate(
            ['jenjang_id' => $s3->id, 'code' => 'FDK'],
            ['name' => 'Fakultas Doktoral']
        );
        Prodi::firstOrCreate(
            ['fakultas_id' => $fakS3->id, 'code' => 'DPAI'],
            ['name' => 'Doktor Pendidikan Agama Islam', 'gender_allowed' => 'ALL']
        );
    }
}
