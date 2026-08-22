<?php

namespace App\Models\Pendaftar;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendidikanPendaftar extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function tingkats()
    {
        return $this->hasMany(TingkatPendidikanPendaftar::class, 'pendidikan_pendaftar_id');
    }
}
