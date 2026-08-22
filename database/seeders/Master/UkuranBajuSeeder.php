<?php

namespace Database\Seeders\Master;

use App\Models\Master\UkuranBaju;
use Illuminate\Database\Seeder;

class UkuranBajuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ukuranList = [
            'S',
            'M',
            'L',
            'XL',
            'XXL',
            'XXXL',
            'Jumbo / Custom',
        ];

        foreach ($ukuranList as $item) {
            UkuranBaju::firstOrCreate(['name' => $item]);
        }
    }
}
