<?php

namespace Database\Seeders\Pendaftaran;

use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Pendaftaran\Periode;
use Illuminate\Database\Seeder;

class PeriodeGelombangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenjangs = Jenjang::all();
        $ta2026 = TahunAkademik::where('name', '2026/2027')->first();

        // Hapus periode lama yang mengandung nama tahun ajaran atau di luar 2026/2027 jika tidak ada relasi penting pendaftar lama
        if ($ta2026) {
            // Update / Create Periode Gelombang 1
            $periode1 = Periode::updateOrCreate(
                [
                    'tahun_akademik_id' => $ta2026->id,
                    'name' => 'Gelombang 1',
                ],
                [
                    'status' => 'buka',
                    'kuota' => null, // Unlimited
                    'jalur_pendaftaran' => 'Semua',
                    'start_date' => '2026-05-01',
                    'end_date' => '2026-07-31',
                ]
            );

            $syncData1 = [];
            foreach ($jenjangs as $j) {
                $syncData1[$j->id] = ['kuota' => null]; // Unlimited kuota per jenjang
            }
            $periode1->jenjangs()->sync($syncData1);

            Gelombang::updateOrCreate(
                [
                    'periode_id' => $periode1->id,
                    'name' => 'Gelombang 1',
                ],
                [
                    'start_date' => '2026-05-01',
                    'end_date' => '2026-07-31',
                ]
            );

            // Update / Create Periode Gelombang 2
            $periode2 = Periode::updateOrCreate(
                [
                    'tahun_akademik_id' => $ta2026->id,
                    'name' => 'Gelombang 2',
                ],
                [
                    'status' => 'buka',
                    'kuota' => null, // Unlimited
                    'jalur_pendaftaran' => 'Semua',
                    'start_date' => '2026-08-01',
                    'end_date' => '2026-09-30',
                ]
            );

            $syncData2 = [];
            foreach ($jenjangs as $j) {
                $syncData2[$j->id] = ['kuota' => null]; // Unlimited kuota per jenjang
            }
            $periode2->jenjangs()->sync($syncData2);

            Gelombang::updateOrCreate(
                [
                    'periode_id' => $periode2->id,
                    'name' => 'Gelombang 2',
                ],
                [
                    'start_date' => '2026-08-01',
                    'end_date' => '2026-09-30',
                ]
            );
        }
    }
}
