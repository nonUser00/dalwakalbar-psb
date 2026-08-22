<?php

namespace App\Models\Pendaftaran;

use App\Enums\StatusPeriode;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    use HasUuids;

    protected $fillable = [
        'tahun_akademik_id',
        'name',
        'status',
        'kuota',
        'start_date',
        'end_date',
        'jalur_pendaftaran',
    ];

    protected $casts = [
        'status' => StatusPeriode::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class);
    }

    public function gelombangs()
    {
        return $this->hasMany(Gelombang::class);
    }

    public function jenjangs()
    {
        return $this->belongsToMany(Jenjang::class, 'periode_jenjang')->withPivot('kuota');
    }
}
