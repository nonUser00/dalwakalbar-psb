<?php

namespace Database\Seeders\Asrama;

use App\Models\Asrama\Rombongan;
use Illuminate\Database\Seeder;

class RombonganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Rombongan Jalur Udara (Pesawat Terbang)
        Rombongan::updateOrCreate(
            ['nama_rombongan' => 'Rombongan Jalur Udara (Pesawat Terbang)'],
            [
                'tanggal_berangkat' => '2026-07-04',
                'biaya' => 2900000,
                'kuota' => 100,
                'titik_kumpul' => 'Bandara Supadio Pontianak',
                'status' => 'BUKA',
            ]
        );

        // 2. Rombongan Jalur Laut (Kapal Penumpang)
        Rombongan::updateOrCreate(
            ['nama_rombongan' => 'Rombongan Jalur Laut (Kapal Penumpang)'],
            [
                'tanggal_berangkat' => '2026-07-02',
                'biaya' => 1650000,
                'kuota' => 150,
                'titik_kumpul' => 'Pelabuhan Dwikora Pontianak',
                'status' => 'BUKA',
            ]
        );
    }
}
