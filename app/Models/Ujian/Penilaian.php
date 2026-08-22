<?php

namespace App\Models\Ujian;

use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penilaian extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pendaftar_id', 'aspek_id', 'penguji_id', 'kelompok_ujian_id', 'nilai', 'catatan',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'pendaftar_id');
    }

    public function aspek(): BelongsTo
    {
        return $this->belongsTo(AspekPenilaian::class, 'aspek_id');
    }

    public function penguji(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_id');
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(KelompokUjian::class, 'kelompok_ujian_id');
    }
}
