<?php

namespace App\Models\Master;

use App\Models\Pendaftaran\Periode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TahunAkademik extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function periodes()
    {
        return $this->hasMany(Periode::class);
    }
}
