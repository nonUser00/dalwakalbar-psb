<?php

namespace Database\Seeders\Pendaftar;

use App\Models\Pendaftar\PendidikanPendaftar;
use App\Models\Pendaftar\TingkatPendidikanPendaftar;
use Illuminate\Database\Seeder;

class PendidikanPendaftarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'tipe' => 'Umum',
                'name' => 'SD',
                'tingkats' => ['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6'],
            ],
            [
                'tipe' => 'Umum',
                'name' => 'SMP',
                'tingkats' => ['Kelas 7', 'Kelas 8', 'Kelas 9'],
            ],
            [
                'tipe' => 'Umum',
                'name' => 'SMA / SMK',
                'tingkats' => ['Kelas 10', 'Kelas 11', 'Kelas 12'],
            ],
            [
                'tipe' => 'Pondok Pesantren',
                'name' => 'Madrasah Ibtidaiyah',
                'tingkats' => ['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6'],
            ],
            [
                'tipe' => 'Pondok Pesantren',
                'name' => 'Madrasah Tsanawiyah',
                'tingkats' => ['Kelas 7', 'Kelas 8', 'Kelas 9'],
            ],
            [
                'tipe' => 'Pondok Pesantren',
                'name' => 'Madrasah Aliyah',
                'tingkats' => ['Kelas 10', 'Kelas 11', 'Kelas 12'],
            ],
            [
                'tipe' => 'Perguruan Tinggi',
                'name' => 'Sarjana (S1)',
                'tingkats' => ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6', 'Semester 7', 'Semester 8'],
            ],
            [
                'tipe' => 'Perguruan Tinggi',
                'name' => 'Magister (S2)',
                'tingkats' => ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4'],
            ],
        ];

        foreach ($data as $item) {
            $pendidikan = PendidikanPendaftar::firstOrCreate([
                'tipe' => $item['tipe'],
                'name' => $item['name'],
            ]);

            foreach ($item['tingkats'] as $tingkatName) {
                TingkatPendidikanPendaftar::firstOrCreate([
                    'pendidikan_pendaftar_id' => $pendidikan->id,
                    'name' => $tingkatName,
                ]);
            }
        }
    }
}
