<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fakultas extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function prodis()
    {
        return $this->hasMany(Prodi::class);
    }
}
