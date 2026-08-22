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
        $ta2025 = TahunAkademik::where('name', '2025/2026')->first();

        if ($ta2026) {
            $periode1 = Periode::firstOrCreate(
                [
                    'tahun_akademik_id' => $ta2026->id,
                    'name' => 'Gelombang 1 TA 2026/2027',
                ],
                [
                    'status' => 'buka',
                    'kuota' => 500,
                    'jalur_pendaftaran' => 'Semua',
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-04-30',
                ]
            );

            $syncData1 = [];
            foreach ($jenjangs as $j) {
                $kuota = match ($j->code) {
                    'MTS' => 150,
                    'MA' => null, // Tanpa Batas
                    'S1' => 100,
                    'S2' => 50,
                    'S3' => 25,
                    default => 100,
                };
                $syncData1[$j->id] = ['kuota' => $kuota];
            }
            $periode1->jenjangs()->sync($syncData1);

            Gelombang::firstOrCreate(
                ['periode_id' => $periode1->id, 'name' => 'Gelombang 1 Utama'],
                ['start_date' => '2026-01-01', 'end_date' => '2026-04-30']
            );

            $periode2 = Periode::firstOrCreate(
                [
                    'tahun_akademik_id' => $ta2026->id,
                    'name' => 'Gelombang 2 TA 2026/2027',
                ],
                [
                    'status' => 'draft',
                    'kuota' => 300,
                    'jalur_pendaftaran' => 'Semua',
                    'start_date' => '2026-05-01',
                    'end_date' => '2026-07-31',
                ]
            );
            $syncData2 = [];
            foreach ($jenjangs as $j) {
                $syncData2[$j->id] = ['kuota' => 50];
            }
            $periode2->jenjangs()->sync($syncData2);

            Gelombang::firstOrCreate(
                ['periode_id' => $periode2->id, 'name' => 'Gelombang 2 Reguler'],
                ['start_date' => '2026-05-01', 'end_date' => '2026-07-31']
            );
        }

        if ($ta2025) {
            $periode2025 = Periode::firstOrCreate(
                [
                    'tahun_akademik_id' => $ta2025->id,
                    'name' => 'Gelombang 1 TA 2025/2026',
                ],
                [
                    'status' => 'tutup',
                    'kuota' => 450,
                    'jalur_pendaftaran' => 'Semua',
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-06-30',
                ]
            );
            $syncData2025 = [];
            foreach ($jenjangs as $j) {
                $syncData2025[$j->id] = ['kuota' => null];
            }
            $periode2025->jenjangs()->sync($syncData2025);

            Gelombang::firstOrCreate(
                ['periode_id' => $periode2025->id, 'name' => 'Gelombang 1 Utama TA 2025/2026'],
                ['start_date' => '2025-01-01', 'end_date' => '2025-06-30']
            );
        }
    }
}
