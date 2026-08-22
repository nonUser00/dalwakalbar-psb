<?php

namespace App\Models\Ujian;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AspekPenilaian extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'kategori_id',
        'nama_aspek',
        'bobot',
        'indikator',
        'urutan',
    ];

    protected $casts = [
        'bobot' => 'integer',
        'urutan' => 'integer',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriPenilaian::class, 'kategori_id');
    }

    public function penilaians()
    {
        return $this->hasMany(Penilaian::class, 'aspek_id');
    }
}
