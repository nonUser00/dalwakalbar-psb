<?php

namespace Database\Seeders\Master;

use App\Models\Master\Dokumen;
use App\Models\Master\Jenjang;
use Illuminate\Database\Seeder;

class DokumenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allJenjangs = Jenjang::all()->keyBy(fn ($j) => strtoupper($j->code ?? $j->singkatan ?? $j->name));
        $allJenjangIds = $allJenjangs->pluck('id')->toArray();

        $dokumenList = [
            [
                'name' => 'Pas Foto Santri (Latar Merah/Biru)',
                'type' => 'gambar',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => true,
                'jenjang_codes' => ['all'],
            ],
            [
                'name' => 'Kartu Keluarga (KK)',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['all'],
            ],
            [
                'name' => 'Akta Kelahiran',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['all'],
            ],
            [
                'name' => 'Ijazah SD/MI / Surat Keterangan Lulus (SKL)',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['MTS'],
            ],
            [
                'name' => 'Ijazah MTs/SMP / Surat Keterangan Lulus (SKL)',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['MA'],
            ],
            [
                'name' => 'Ijazah MA/SMA / SKL & Transkrip Nilai',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S1'],
            ],
            [
                'name' => 'Ijazah S1 & Transkrip Nilai Akademik',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S2'],
            ],
            [
                'name' => 'Ijazah S2 & Transkrip Nilai Akademik',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S3'],
            ],
            [
                'name' => 'Surat Keterangan Pindah / Mutasi',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Pindahan',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['MTS', 'MA'],
            ],
            [
                'name' => 'Surat Rekomendasi Tokoh / Pimpinan Pesantren',
                'type' => 'pdf',
                'jalur_pendaftaran' => 'Semua',
                'is_required' => false,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S1', 'S2', 'S3'],
            ],
        ];

        foreach ($dokumenList as $item) {
            $jenjangCodes = $item['jenjang_codes'];
            unset($item['jenjang_codes']);

            $dokumen = Dokumen::updateOrCreate(
                ['name' => $item['name']],
                $item
            );

            if (in_array('all', $jenjangCodes)) {
                $dokumen->jenjangs()->sync($allJenjangIds);
            } else {
                $syncIds = [];
                foreach ($jenjangCodes as $code) {
                    // Match by code or loose match
                    $matched = $allJenjangs->filter(function ($j, $key) use ($code) {
                        return str_contains(strtoupper($key), strtoupper($code))
                            || str_contains(strtoupper($j->name), strtoupper($code))
                            || strtoupper($j->code ?? '') === strtoupper($code);
                    });
                    foreach ($matched as $m) {
                        $syncIds[] = $m->id;
                    }
                }
                $dokumen->jenjangs()->sync(array_unique($syncIds));
            }
        }
    }
}
