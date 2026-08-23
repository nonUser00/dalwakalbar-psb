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
                'type' => 'semua', // Dokumen & Gambar (PDF, JPG, PNG)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['all'],
            ],
            [
                'name' => 'Akta Kelahiran',
                'type' => 'semua', // Dokumen & Gambar (PDF, JPG, PNG)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['all'],
            ],
            [
                'name' => 'Ijazah SD/MI/Surat Keterangan Lulus (SKL)',
                'type' => 'semua', // Dokumen & Gambar (PDF, JPG, PNG)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['MTS'],
            ],
            [
                'name' => 'Ijazah SMP/MTs/Surat Keterangan Lulus (SKL)',
                'type' => 'semua', // Dokumen & Gambar (PDF, JPG, PNG)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['MA'],
            ],
            [
                'name' => 'Ijazah SMA/SMK/MA/Surat Keterangan Lulus (SKL)',
                'type' => 'semua', // Dokumen & Gambar (PDF, JPG, PNG)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S1'],
            ],
            [
                'name' => 'Ijazah S1 & Transkrip Nilai Akademik',
                'type' => 'pdf', // Dokumen (PDF)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S2'],
            ],
            [
                'name' => 'Ijazah S2 & Transkrip Nilai Akademik',
                'type' => 'pdf', // Dokumen (PDF)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S3'],
            ],
            [
                'name' => 'Surat Keterangan Pindah/Mutasi',
                'type' => 'semua', // Dokumen & Gambar (PDF, JPG, PNG)
                'jalur_pendaftaran' => 'Pindahan',
                'is_required' => true,
                'is_profile_photo' => false,
                'jenjang_codes' => ['all'],
            ],
            [
                'name' => 'Surat Rekomendasi Tokoh/Pimpinan Pesantren',
                'type' => 'semua', // Dokumen & Gambar (PDF, JPG, PNG)
                'jalur_pendaftaran' => 'Semua',
                'is_required' => false,
                'is_profile_photo' => false,
                'jenjang_codes' => ['S2', 'S3'],
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
                    $matched = $allJenjangs->filter(function ($j) use ($code) {
                        return strtoupper($j->code ?? '') === strtoupper($code)
                            || strtoupper($j->singkatan ?? '') === strtoupper($code);
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
