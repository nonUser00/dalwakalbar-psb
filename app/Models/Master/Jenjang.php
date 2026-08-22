<?php

namespace App\Models\Master;

use App\Enums\GenderAllowed;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jenjang extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'gender_allowed' => GenderAllowed::class,
        'is_active' => 'boolean',
    ];

    public function tingkats()
    {
        return $this->hasMany(Tingkat::class);
    }

    public function jurusans()
    {
        return $this->hasMany(Jurusan::class);
    }

    public function fakultas()
    {
        return $this->hasMany(Fakultas::class);
    }
}
