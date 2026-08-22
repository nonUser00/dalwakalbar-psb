<?php

namespace Database\Seeders\Keuangan;

use App\Models\Keuangan\ItemBiaya;
use App\Models\Keuangan\KategoriBiaya;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use Illuminate\Database\Seeder;

class BiayaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. BIAYA PENDAFTARAN (PER-JENJANG)
        $jenjangs = Jenjang::all();

        foreach ($jenjangs as $jenjang) {
            $kategoriPendaftaran = KategoriBiaya::firstOrCreate([
                'jenis' => 'pendaftaran',
                'jenjang_id' => $jenjang->id,
                'name' => 'Biaya Pendaftaran & Formulir',
            ]);

            ItemBiaya::firstOrCreate([
                'kategori_biaya_id' => $kategoriPendaftaran->id,
                'name' => 'Biaya Formulir & Administrasi Pendaftaran',
            ], ['nominal' => 250000]);

            $kategoriDaftarUlang = KategoriBiaya::firstOrCreate([
                'jenis' => 'pendaftaran',
                'jenjang_id' => $jenjang->id,
                'name' => 'Biaya Daftar Ulang & Masuk Pondok',
            ]);

            ItemBiaya::firstOrCreate([
                'kategori_biaya_id' => $kategoriDaftarUlang->id,
                'name' => 'Biaya Perlengkapan, Kitab & Seragam',
            ], ['nominal' => 1200000]);

            ItemBiaya::firstOrCreate([
                'kategori_biaya_id' => $kategoriDaftarUlang->id,
                'name' => 'Biaya Pembinaan & Pengembangan Santri',
            ], ['nominal' => 800000]);
        }

        // 2. BIAYA ROMBONGAN (PER-CABANG & PER-JENIS ROMBONGAN: PESAWAT / KAPAL)
        $cabangs = Cabang::all();

        foreach ($cabangs as $cabang) {
            // Rombongan Pesawat
            $kategoriPesawat = KategoriBiaya::firstOrCreate([
                'jenis' => 'rombongan',
                'cabang_id' => $cabang->id,
                'jenis_rombongan' => 'PESAWAT',
                'name' => 'Biaya Keberangkatan Rombongan Pesawat ('.$cabang->name.')',
            ]);

            ItemBiaya::firstOrCreate([
                'kategori_biaya_id' => $kategoriPesawat->id,
                'name' => 'Tiket Pesawat & Bagasi',
            ], ['nominal' => 1750000]);

            ItemBiaya::firstOrCreate([
                'kategori_biaya_id' => $kategoriPesawat->id,
                'name' => 'Transportasi Bandara & Pendamping',
            ], ['nominal' => 250000]);

            // Rombongan Kapal
            $kategoriKapal = KategoriBiaya::firstOrCreate([
                'jenis' => 'rombongan',
                'cabang_id' => $cabang->id,
                'jenis_rombongan' => 'KAPAL',
                'name' => 'Biaya Keberangkatan Rombongan Kapal Laut ('.$cabang->name.')',
            ]);

            ItemBiaya::firstOrCreate([
                'kategori_biaya_id' => $kategoriKapal->id,
                'name' => 'Tiket Kapal Laut & Tempat Tidur',
            ], ['nominal' => 650000]);

            ItemBiaya::firstOrCreate([
                'kategori_biaya_id' => $kategoriKapal->id,
                'name' => 'Konsumsi Perjalanan & Transportasi Pelabuhan',
            ], ['nominal' => 150000]);
        }

        // 3. BIAYA INTERVIEW (GLOBAL)
        $kategoriInterview = KategoriBiaya::firstOrCreate([
            'jenis' => 'interview',
            'name' => 'Biaya Ujian Seleksi & Interview',
        ]);

        ItemBiaya::firstOrCreate([
            'kategori_biaya_id' => $kategoriInterview->id,
            'name' => 'Biaya Penguji & Ujian Masuk',
        ], ['nominal' => 150000]);

        ItemBiaya::firstOrCreate([
            'kategori_biaya_id' => $kategoriInterview->id,
            'name' => 'Biaya Administrasi & Sertifikat Hasil Ujian',
        ], ['nominal' => 50000]);

        // 4. BIAYA TAGIHAN LAINNYA / BIASA (GLOBAL)
        $kategoriSPP = KategoriBiaya::firstOrCreate([
            'jenis' => 'lainnya',
            'name' => 'Biaya SPP & Syahriah Bulanan',
        ]);

        ItemBiaya::firstOrCreate([
            'kategori_biaya_id' => $kategoriSPP->id,
            'name' => 'Syahriah Bulanan / SPP Pendidikan',
        ], ['nominal' => 600000]);

        $kategoriKegiatan = KategoriBiaya::firstOrCreate([
            'jenis' => 'lainnya',
            'name' => 'Biaya Ekstrakurikuler & Kegiatan Santri',
        ]);

        ItemBiaya::firstOrCreate([
            'kategori_biaya_id' => $kategoriKegiatan->id,
            'name' => 'Iuran Kegiatan & Organisasi Santri',
        ], ['nominal' => 100000]);
    }
}
