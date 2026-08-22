<?php

namespace App\Models\Ujian;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriPenilaian extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['nama_kategori', 'keterangan', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function aspek_penilaians()
    {
        return $this->hasMany(AspekPenilaian::class, 'kategori_id');
    }
}
