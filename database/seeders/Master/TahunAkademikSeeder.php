<?php

namespace Database\Seeders\Master;

use App\Models\Master\TahunAkademik;
use Illuminate\Database\Seeder;

class TahunAkademikSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tahun Akademik 2026/2027 (Aktif)
        TahunAkademik::firstOrCreate(
            ['name' => '2026/2027'],
            ['is_active' => true]
        );

        // 2. Tahun Akademik 2025/2026 (Non Aktif)
        TahunAkademik::firstOrCreate(
            ['name' => '2025/2026'],
            ['is_active' => false]
        );
    }
}
