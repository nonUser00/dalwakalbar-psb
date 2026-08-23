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
            $code = strtoupper($jenjang->code ?? $jenjang->singkatan ?? '');

            // Bersihkan kategori pendaftaran lama jika ada
            $oldKategoris = KategoriBiaya::where('jenis', 'pendaftaran')
                ->where('jenjang_id', $jenjang->id)
                ->where('name', '!=', 'Administrasi Pendaftaran')
                ->get();
            foreach ($oldKategoris as $oldKategori) {
                ItemBiaya::where('kategori_biaya_id', $oldKategori->id)->delete();
                $oldKategori->delete();
            }

            $kategoriPendaftaran = KategoriBiaya::updateOrCreate([
                'jenis' => 'pendaftaran',
                'jenjang_id' => $jenjang->id,
            ], [
                'name' => 'Administrasi Pendaftaran',
            ]);

            ItemBiaya::where('kategori_biaya_id', $kategoriPendaftaran->id)->delete();

            // 1. Biaya Wilayah
            ItemBiaya::create([
                'kategori_biaya_id' => $kategoriPendaftaran->id,
                'name' => 'Biaya wilayah (termasuk kartu peserta tes & wawancara)',
                'nominal' => 365000,
            ]);

            // 2. Biaya Administrasi & Cetak Formulir
            ItemBiaya::create([
                'kategori_biaya_id' => $kategoriPendaftaran->id,
                'name' => 'Biaya administrasi & cetak formulir',
                'nominal' => 800000,
            ]);

            // 3. Uang Pangkal Program Pondok Saja
            ItemBiaya::create([
                'kategori_biaya_id' => $kategoriPendaftaran->id,
                'name' => 'Uang pangkal — program pondok saja',
                'nominal' => 13000000,
            ]);

            // 4. Uang Pangkal Formal sesuai Jenjang
            $formalUangPangkalName = match ($code) {
                'MTS' => 'Uang pangkal — pondok + formal MTs',
                'MA' => 'Uang pangkal — pondok + formal MA',
                'S1' => 'Uang pangkal — pondok + formal S1',
                'S2' => 'Uang pangkal — pondok + formal S2',
                'S3' => 'Uang pangkal — pondok + formal S3',
                default => 'Uang pangkal — pondok + formal '.$code,
            };

            $formalUangPangkalNominal = in_array($code, ['S1', 'S2', 'S3']) ? 15500000 : 13500000;

            ItemBiaya::create([
                'kategori_biaya_id' => $kategoriPendaftaran->id,
                'name' => $formalUangPangkalName,
                'nominal' => $formalUangPangkalNominal,
            ]);
        }

        // 2. BIAYA ROMBONGAN (PER-CABANG & PER-JENIS ROMBONGAN: PESAWAT / KAPAL)
        $cabangs = Cabang::all();

        foreach ($cabangs as $cabang) {
            // Rombongan Jalur Udara (Pesawat Terbang) - Total Rp 2.900.000
            $kategoriPesawat = KategoriBiaya::updateOrCreate([
                'jenis' => 'rombongan',
                'cabang_id' => $cabang->id,
                'jenis_rombongan' => 'PESAWAT',
            ], [
                'name' => 'Biaya Keberangkatan Rombongan Pesawat ('.$cabang->name.')',
            ]);

            // Bersihkan item biaya lama
            ItemBiaya::where('kategori_biaya_id', $kategoriPesawat->id)->delete();

            $pesawatItems = [
                ['name' => 'Tiket Pesawat Rute Pontianak – Surabaya & Bagasi', 'nominal' => 2050000],
                ['name' => 'Transportasi Bandara Kedatangan – Penginapan', 'nominal' => 150000],
                ['name' => 'Akomodasi Penginapan (2 Malam)', 'nominal' => 300000],
                ['name' => 'Konsumsi di Penginapan (5 Kali Makan)', 'nominal' => 150000],
                ['name' => 'Transportasi Tes Kesehatan & Administrasi PP', 'nominal' => 100000],
                ['name' => 'Transportasi Penginapan Menuju Pondok Pesantren', 'nominal' => 150000],
            ];

            foreach ($pesawatItems as $item) {
                ItemBiaya::create([
                    'kategori_biaya_id' => $kategoriPesawat->id,
                    'name' => $item['name'],
                    'nominal' => $item['nominal'],
                ]);
            }

            // Rombongan Jalur Laut (Kapal Penumpang) - Total Rp 1.650.000
            $kategoriKapal = KategoriBiaya::updateOrCreate([
                'jenis' => 'rombongan',
                'cabang_id' => $cabang->id,
                'jenis_rombongan' => 'KAPAL',
            ], [
                'name' => 'Biaya Keberangkatan Rombongan Kapal Laut ('.$cabang->name.')',
            ]);

            // Bersihkan item biaya lama
            ItemBiaya::where('kategori_biaya_id', $kategoriKapal->id)->delete();

            $kapalItems = [
                ['name' => 'Tiket Kapal Laut Rute Pontianak – Surabaya', 'nominal' => 800000],
                ['name' => 'Transportasi Pelabuhan Kedatangan – Penginapan', 'nominal' => 150000],
                ['name' => 'Akomodasi Penginapan (2 Malam)', 'nominal' => 300000],
                ['name' => 'Konsumsi di Penginapan (5 Kali Makan)', 'nominal' => 150000],
                ['name' => 'Transportasi Tes Kesehatan & Administrasi PP', 'nominal' => 100000],
                ['name' => 'Transportasi Penginapan Menuju Pondok Pesantren', 'nominal' => 150000],
            ];

            foreach ($kapalItems as $item) {
                ItemBiaya::create([
                    'kategori_biaya_id' => $kategoriKapal->id,
                    'name' => $item['name'],
                    'nominal' => $item['nominal'],
                ]);
            }
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
