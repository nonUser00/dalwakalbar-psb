<?php

namespace App\Models\Ujian;

use Illuminate\Database\Eloquent\Model;

class HasilWawancara extends Model
{
    protected $fillable = [
        'hasil_ujian_id',
        'current_step',

        'motivasi_cita_cita',
        'motivasi_bersedia_4_tahun',
        'motivasi_keinginan_mondok',
        'motivasi_kenalan_nama',
        'motivasi_kenalan_hubungan',
        'motivasi_tidak_ambil_ijazah',
        'motivasi_catatan',

        'kebiasaan_jam_tidur',
        'kebiasaan_jam_bangun',
        'kebiasaan_kegiatan_malam',
        'kebiasaan_riwayat_penyakit',

        'ibadah_sholat_5_waktu',
        'ibadah_sholat_berjamaah',
        'ibadah_bacaan_sholat',
        'ibadah_shodaqoh',
        'ibadah_membantu_orang',
        'ibadah_catatan',
        'ibadah_bacaan_catatan',

        'pelanggaran_pernah_dilakukan',
        'pelanggaran_catatan',

        'prestasi_items',
        'prestasi_catatan_sekolah',
        'prestasi_catatan_pondok',
    ];

    protected $casts = [
        'ibadah_bacaan_sholat' => 'array',
        'pelanggaran_pernah_dilakukan' => 'array',
        'prestasi_items' => 'array',
    ];

    public function hasilUjian()
    {
        return $this->belongsTo(HasilUjian::class, 'hasil_ujian_id');
    }
}
