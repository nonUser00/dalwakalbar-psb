<?php

namespace Database\Seeders\Master;

use App\Models\Master\Cabang;
use Illuminate\Database\Seeder;

class CabangSeeder extends Seeder
{
    public function run(): void
    {
        Cabang::updateOrCreate(
            ['name' => 'Kalimantan Barat'],
            [
                'singkatan' => 'Kalbar',
                'logo_path' => 'image/cabang/kalbar.svg',
                'is_active' => true,
            ]
        );

        Cabang::updateOrCreate(
            ['name' => 'Kalimantan Timur'],
            [
                'singkatan' => 'Kaltim',
                'logo_path' => 'image/cabang/kaltim.png',
                'is_active' => true,
            ]
        );
    }
}
