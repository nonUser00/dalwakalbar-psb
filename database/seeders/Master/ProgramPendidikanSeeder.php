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
        // =========================================================================
        // 1. MTs (Madrasah Tsanawiyah)
        // =========================================================================
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
            Tingkat::updateOrCreate(
                ['jenjang_id' => $mts->id, 'name' => $t],
                ['gender_allowed' => 'ALL']
            );
        }

        // =========================================================================
        // 2. MA (Madrasah Aliyah)
        // =========================================================================
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
            Tingkat::updateOrCreate(
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
            Jurusan::updateOrCreate(
                ['jenjang_id' => $ma->id, 'code' => $j['code']],
                ['name' => $j['name'], 'gender_allowed' => $j['gender_allowed']]
            );
        }

        // =========================================================================
        // 3. Program Strata 1 (S1)
        // =========================================================================
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
        foreach (['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6', 'Semester 7', 'Semester 8'] as $t) {
            Tingkat::updateOrCreate(
                ['jenjang_id' => $s1->id, 'name' => $t],
                ['gender_allowed' => 'ALL']
            );
        }

        $fakultasS1 = [
            [
                'code' => 'FT',
                'name' => 'Fakultas Tarbiyah',
                'prodis' => [
                    ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam (PAI)', 'gender_allowed' => 'ALL'],
                    ['code' => 'PBA', 'name' => 'Pendidikan Bahasa Arab (PBA)', 'gender_allowed' => 'ALL'],
                    ['code' => 'MPI', 'name' => 'Manajemen Pendidikan Islam (MPI)', 'gender_allowed' => 'ALL'],
                ],
            ],
            [
                'code' => 'FS',
                'name' => 'Fakultas Syariah',
                'prodis' => [
                    ['code' => 'ESY', 'name' => 'Ekonomi Syariah (ESY)', 'gender_allowed' => 'ALL'],
                    ['code' => 'HKI', 'name' => 'Hukum Keluarga Islam (HKI)', 'gender_allowed' => 'L'],
                ],
            ],
            [
                'code' => 'FUA',
                'name' => 'Fakultas Ushuluddin dan Adab',
                'prodis' => [
                    ['code' => 'SPI', 'name' => 'Sejarah Peradaban Islam (SPI)', 'gender_allowed' => 'L'],
                    ['code' => 'BSA', 'name' => 'Sastra Arab', 'gender_allowed' => 'L'],
                    ['code' => 'IAT', 'name' => 'Ilmu Al-Qur\'an dan Tafsir', 'gender_allowed' => 'L'],
                ],
            ],
            [
                'code' => 'FD',
                'name' => 'Fakultas Dakwah',
                'prodis' => [
                    ['code' => 'KPI', 'name' => 'Komunikasi Penyiaran Islam (KPI)', 'gender_allowed' => 'L'],
                    ['code' => 'BKI', 'name' => 'Bimbingan Konseling Islam (BKI)', 'gender_allowed' => 'ALL'],
                    ['code' => 'MHU', 'name' => 'Manajemen Haji dan Umrah (MHU)', 'gender_allowed' => 'L'],
                ],
            ],
        ];
        $this->seedFakultasAndProdis($s1, $fakultasS1);

        // =========================================================================
        // 4. Program Strata 2 (S2)
        // =========================================================================
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
        foreach (['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4'] as $t) {
            Tingkat::updateOrCreate(
                ['jenjang_id' => $s2->id, 'name' => $t],
                ['gender_allowed' => 'ALL']
            );
        }

        $fakultasS2 = [
            [
                'code' => 'FT-S2',
                'name' => 'Fakultas Tarbiyah',
                'prodis' => [
                    ['code' => 'PBA-S2', 'name' => 'Pendidikan Bahasa Arab', 'gender_allowed' => 'ALL'],
                    ['code' => 'MPI-S2', 'name' => 'Manajemen Pendidikan Islam', 'gender_allowed' => 'ALL'],
                    ['code' => 'PAI-S2', 'name' => 'Pendidikan Agama Islam', 'gender_allowed' => 'ALL'],
                ],
            ],
            [
                'code' => 'FU-S2',
                'name' => 'Fakultas Ushuluddin',
                'prodis' => [
                    ['code' => 'SI-S2', 'name' => 'Studi Islam', 'gender_allowed' => 'L'],
                ],
            ],
        ];
        $this->seedFakultasAndProdis($s2, $fakultasS2);

        // =========================================================================
        // 5. Program Strata 3 (S3)
        // =========================================================================
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
        foreach (['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6'] as $t) {
            Tingkat::updateOrCreate(
                ['jenjang_id' => $s3->id, 'name' => $t],
                ['gender_allowed' => 'ALL']
            );
        }

        $fakultasS3 = [
            [
                'code' => 'FT-S3',
                'name' => 'Fakultas Tarbiyah',
                'prodis' => [
                    ['code' => 'PAI-S3', 'name' => 'Pendidikan Agama Islam', 'gender_allowed' => 'ALL'],
                    ['code' => 'PBA-S3', 'name' => 'Pendidikan Bahasa Arab', 'gender_allowed' => 'ALL'],
                ],
            ],
        ];
        $this->seedFakultasAndProdis($s3, $fakultasS3);
    }

    /**
     * Helper to seed Fakultas and its Prodis.
     */
    private function seedFakultasAndProdis(Jenjang $jenjang, array $fakultasList): void
    {
        $existingFakIds = [];

        foreach ($fakultasList as $f) {
            $fak = Fakultas::updateOrCreate(
                ['jenjang_id' => $jenjang->id, 'name' => $f['name']],
                ['code' => $f['code']]
            );
            $existingFakIds[] = $fak->id;

            $existingProdiIds = [];
            foreach ($f['prodis'] as $p) {
                $prodi = Prodi::updateOrCreate(
                    ['fakultas_id' => $fak->id, 'name' => $p['name']],
                    [
                        'code' => $p['code'],
                        'gender_allowed' => $p['gender_allowed'],
                    ]
                );
                $existingProdiIds[] = $prodi->id;
            }

            // Cleanup obsolete prodis in this fakultas if any
            Prodi::where('fakultas_id', $fak->id)
                ->whereNotIn('id', $existingProdiIds)
                ->delete();
        }

        // Cleanup obsolete fakultas in this jenjang if any
        Fakultas::where('jenjang_id', $jenjang->id)
            ->whereNotIn('id', $existingFakIds)
            ->delete();
    }
}
