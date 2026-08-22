<?php

namespace App\Models\Ujian;

use App\Enums\StatusKelulusan;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HasilUjian extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'pendaftar_id',
        'kelompok_ujian_id',
        'nilai_menulis',
        'predikat_menulis',
        'nilai_baca_kitab',
        'predikat_baca_kitab',
        'nilai_hafalan',
        'predikat_hafalan',
        'nilai_wawancara',
        'hasil_wawancara',
        'total_nilai',
        'rekomendasi_kelas_pondok',
        'status_kelulusan',
        'catatan_final',
        'nomor_surat_hasil',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'nilai_menulis' => 'float',
        'nilai_baca_kitab' => 'float',
        'nilai_hafalan' => 'float',
        'nilai_wawancara' => 'float',
        'total_nilai' => 'float',
        'status_kelulusan' => StatusKelulusan::class,
        'locked_at' => 'datetime',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'pendaftar_id');
    }

    public function kelompokUjian(): BelongsTo
    {
        return $this->belongsTo(KelompokUjian::class, 'kelompok_ujian_id');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function dataWawancara()
    {
        return $this->hasOne(HasilWawancara::class, 'hasil_ujian_id');
    }
}
