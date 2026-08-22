<?php

namespace Database\Seeders\Master;

use App\Models\Master\PendidikanOrtu;
use Illuminate\Database\Seeder;

class PendidikanOrtuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pendidikanList = [
            'Tidak Sekolah / Belum Sekolah',
            'SD / MI / Sederajat',
            'SMP / MTs / Sederajat',
            'SMA / MA / SMK / Sederajat',
            'Diploma (D1 / D2 / D3 / D4)',
            'S1 (Sarjana)',
            'S2 (Magister)',
            'S3 (Doktor)',
        ];

        foreach ($pendidikanList as $item) {
            PendidikanOrtu::firstOrCreate(['name' => $item]);
        }
    }
}
