<?php

namespace Database\Seeders\Setting;

use App\Models\Setting\NumberingSequence;
use App\Models\Setting\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Settings
        $settings = [
            [
                'group' => 'umum',
                'key' => 'kontak_darurat_wa',
                'value' => '087818224709',
                'type' => 'string',
            ],
            [
                'group' => 'umum',
                'key' => 'nama_contact',
                'value' => 'Perwakilan Kalimantan Barat - Pondok Pesantren Darullughah Wadda\'wah',
                'type' => 'string',
            ],
            [
                'group' => 'umum',
                'key' => 'hari_kerja',
                'value' => json_encode(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
                'type' => 'json',
            ],
            [
                'group' => 'umum',
                'key' => 'jam_kerja_mulai',
                'value' => '08:00',
                'type' => 'string',
            ],
            [
                'group' => 'umum',
                'key' => 'jam_kerja_selesai',
                'value' => '17:00',
                'type' => 'string',
            ],
            [
                'group' => 'cetak',
                'key' => 'kop_surat_path',
                'value' => null, // Path relative to storage
                'type' => 'image',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['id' => Str::uuid()])
            );
        }

        // Numbering Sequences
        NumberingSequence::firstOrCreate(
            ['name' => 'nomor_pendaftaran'],
            [
                'id' => Str::uuid(),
                'prefix' => 'PSB',
                'pattern' => '{PREFIX}-{YYYY}-{AUTONUMBER}',
                'padding' => 4,
                'next_number' => 1,
            ]
        );

        NumberingSequence::firstOrCreate(
            ['name' => 'nomor_invoice'],
            [
                'id' => Str::uuid(),
                'prefix' => 'INV',
                'pattern' => '{PREFIX}-{YYYY}{MM}-{AUTONUMBER}',
                'padding' => 5,
                'next_number' => 1,
            ]
        );
    }
}
