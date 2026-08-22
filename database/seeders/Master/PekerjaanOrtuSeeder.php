<?php

namespace Database\Seeders\Master;

use App\Models\Master\PekerjaanOrtu;
use Illuminate\Database\Seeder;

class PekerjaanOrtuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pekerjaanList = [
            ['name' => 'PNS / TNI / Polri', 'is_lainnya' => false],
            ['name' => 'Wiraswasta / Pengusaha', 'is_lainnya' => false],
            ['name' => 'Pegawai Swasta', 'is_lainnya' => false],
            ['name' => 'Petani / Peternak', 'is_lainnya' => false],
            ['name' => 'Nelayan', 'is_lainnya' => false],
            ['name' => 'Guru / Dosen / Tenaga Pendidik', 'is_lainnya' => false],
            ['name' => 'Pedagang', 'is_lainnya' => false],
            ['name' => 'Buruh Harian Lepas', 'is_lainnya' => false],
            ['name' => 'Ibu Rumah Tangga', 'is_lainnya' => false],
            ['name' => 'Pensiunan', 'is_lainnya' => false],
            ['name' => 'Tidak Bekerja', 'is_lainnya' => false],
            ['name' => 'Pekerjaan Lainnya', 'is_lainnya' => true],
        ];

        foreach ($pekerjaanList as $item) {
            PekerjaanOrtu::firstOrCreate(
                ['name' => $item['name']],
                ['is_lainnya' => $item['is_lainnya']]
            );
        }
    }
}
