<?php

namespace App\Models\Master;

use App\Enums\GenderAllowed;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'gender_allowed' => GenderAllowed::class,
    ];

    public function fakultas()
    {
        return $this->belongsTo(Fakultas::class);
    }
}
