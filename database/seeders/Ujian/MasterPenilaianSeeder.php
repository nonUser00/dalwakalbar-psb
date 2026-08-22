<?php

namespace Database\Seeders\Ujian;

use App\Models\Ujian\AspekPenilaian;
use App\Models\Ujian\KategoriPenilaian;
use Illuminate\Database\Seeder;

class MasterPenilaianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'nama_kategori' => 'Tes Menulis',
                'keterangan' => 'Penilaian tes imla/dikte Arab, ketepatan ejaan & huruf, kelengkapan kata & kalimat, keindahan & kerapian tulisan, adab & kecepatan waktu, dan pemahaman makna.',
                'is_active' => true,
                'aspeks' => [
                    [
                        'nama_aspek' => 'Ketepatan Ejaan & Huruf',
                        'bobot' => 30,
                        'urutan' => 1,
                        'indikator' => 'Penulisan huruf hijaiyah benar, tidak ada huruf yang tertukar (ض/ظ, ح/خ, س/ص, ث/ت, dll.). Penulisan harakat (fathah, kasrah, dhammah, sukun, tanwin) tepat dan lengkap.',
                    ],
                    [
                        'nama_aspek' => 'Kelengkapan Kata & Kalimat',
                        'bobot' => 25,
                        'urutan' => 2,
                        'indikator' => 'Seluruh kata dan kalimat yang didiktekan ditulis tanpa ada yang terlewat. Urutan kata dan struktur kalimat sesuai dengan yang didiktekan.',
                    ],
                    [
                        'nama_aspek' => 'Keindahan & Kerapian Tulisan',
                        'bobot' => 25,
                        'urutan' => 3,
                        'indikator' => 'Bentuk huruf jelas, proporsional, dan mudah dibaca (tidak ambigu). Penulisan rapi: tidak ada coretan berlebihan, jarak antar kata proporsional.',
                    ],
                    [
                        'nama_aspek' => 'Adab & Kecepatan Ketepatan Waktu',
                        'bobot' => 15,
                        'urutan' => 4,
                        'indikator' => 'Mampu menulis mengikuti kecepatan dikte tanpa ketinggalan.',
                    ],
                    [
                        'nama_aspek' => 'Pemahaman Makna (Opsional)',
                        'bobot' => 5,
                        'urutan' => 5,
                        'indikator' => 'Mampu menulis dengan memperhatikan konteks kalimat (bukan sekadar menyalin bunyi).',
                    ],
                ],
            ],
            [
                'nama_kategori' => 'Tes Membaca',
                'keterangan' => 'Penilaian kelancaran membaca kitab/teks Arab, ketepatan harakat/i\'rab, kaidah nahwu & sharaf, terjemah & pemahaman isi, serta adab dan kepercayaan diri.',
                'is_active' => true,
                'aspeks' => [
                    [
                        'nama_aspek' => 'Kelancaran Membaca',
                        'bobot' => 20,
                        'urutan' => 1,
                        'indikator' => 'Membaca teks dengan lancar, tidak banyak terhenti, mampu menyelesaikan bacaan sesuai waktu yang ditentukan.',
                    ],
                    [
                        'nama_aspek' => 'Ketepatan Harakat / I\'rab',
                        'bobot' => 25,
                        'urutan' => 2,
                        'indikator' => 'Memberikan harakat yang sesuai berdasarkan kaidah nahwu, menentukan posisi i\'rab dengan benar.',
                    ],
                    [
                        'nama_aspek' => 'Nahwu dan Sharaf',
                        'bobot' => 25,
                        'urutan' => 3,
                        'indikator' => 'Mampu menjelaskan fungsi kata dalam kalimat (nahwu: 15%) serta mengidentifikasi bentuk kata, wazan, dan tashrif (sharaf: 10%).',
                    ],
                    [
                        'nama_aspek' => 'Terjemah dan Pemahaman Isi',
                        'bobot' => 20,
                        'urutan' => 4,
                        'indikator' => 'Mengetahui arti mufradat, menerjemahkan kalimat secara tepat dan runtut, serta menjelaskan maksud/pokok bahasan isi bacaan.',
                    ],
                    [
                        'nama_aspek' => 'Adab dan Kepercayaan Diri',
                        'bobot' => 10,
                        'urutan' => 5,
                        'indikator' => 'Menunjukkan sikap sopan, tenang, dan percaya diri saat membaca serta menjawab pertanyaan.',
                    ],
                ],
            ],
            [
                'nama_kategori' => 'Tes Hafalan',
                'keterangan' => 'Ujian kelancaran hafalan Al-Qur\'an, kaidah tajwid, makharijul huruf, serta adab dan tartil.',
                'is_active' => true,
                'aspeks' => [
                    [
                        'nama_aspek' => 'Kelancaran',
                        'bobot' => 30,
                        'urutan' => 1,
                        'indikator' => 'Hafalan lancar tanpa berhenti/ragu-ragu; tidak ada pengulangan yang tidak perlu.',
                    ],
                    [
                        'nama_aspek' => 'Tajwid',
                        'bobot' => 30,
                        'urutan' => 2,
                        'indikator' => 'Penerapan hukum nun mati/tanwin, mim mati, mad, ghunnah, qalqalah, waqaf, dan ibtida\'.',
                    ],
                    [
                        'nama_aspek' => 'Makhraj',
                        'bobot' => 20,
                        'urutan' => 3,
                        'indikator' => 'Pengucapan huruf sesuai makharijul huruf; membedakan huruf serupa (ح/خ, س/ص, dll.).',
                    ],
                    [
                        'nama_aspek' => 'Adab & Tartil',
                        'bobot' => 20,
                        'urutan' => 4,
                        'indikator' => 'Membaca dengan tenang, khusyu\', tartil; memperhatikan waqaf dan washol.',
                    ],
                ],
            ],
            [
                'nama_kategori' => 'Wawancara & Kepribadian',
                'keterangan' => 'Wawancara kesiapan mondok, riwayat perilaku, dan evaluasi bacaan sholat.',
                'is_active' => true,
                'aspeks' => [
                    [
                        'nama_aspek' => 'Motivasi & Kesiapan Mondok',
                        'bobot' => 50,
                        'urutan' => 1,
                        'indikator' => 'Kesiapan mental, kemandirian, dan komitmen calon santri menempuh pendidikan di pondok pesantren.',
                    ],
                    [
                        'nama_aspek' => 'Ibadah & Karakter Santri',
                        'bobot' => 50,
                        'urutan' => 2,
                        'indikator' => 'Praktik ibadah sehari-hari, bacaan sholat, adab kepada orang tua/guru, dan rekam jejak akhlaq.',
                    ],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $kategori = KategoriPenilaian::updateOrCreate([
                'nama_kategori' => $catData['nama_kategori'],
            ], [
                'keterangan' => $catData['keterangan'],
                'is_active' => $catData['is_active'],
            ]);

            // Clean up obsolete aspects that are not in the new items
            $currentAspekNames = array_column($catData['aspeks'], 'nama_aspek');
            AspekPenilaian::where('kategori_id', $kategori->id)
                ->whereNotIn('nama_aspek', $currentAspekNames)
                ->delete();

            foreach ($catData['aspeks'] as $aspekData) {
                AspekPenilaian::updateOrCreate([
                    'kategori_id' => $kategori->id,
                    'nama_aspek' => $aspekData['nama_aspek'],
                ], [
                    'bobot' => $aspekData['bobot'],
                    'indikator' => $aspekData['indikator'],
                    'urutan' => $aspekData['urutan'],
                ]);
            }
        }
    }
}
