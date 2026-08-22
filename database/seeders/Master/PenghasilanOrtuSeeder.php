<?php

namespace Database\Seeders\Master;

use App\Models\Master\PenghasilanOrtu;
use Illuminate\Database\Seeder;

class PenghasilanOrtuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $penghasilanList = [
            'Tidak Berpenghasilan',
            'Kurang dari Rp 1.000.000',
            'Rp 1.000.000 - Rp 2.500.000',
            'Rp 2.500.000 - Rp 5.000.000',
            'Rp 5.000.000 - Rp 10.000.000',
            'Lebih dari Rp 10.000.000',
        ];

        foreach ($penghasilanList as $item) {
            PenghasilanOrtu::firstOrCreate(['name' => $item]);
        }
    }
}
