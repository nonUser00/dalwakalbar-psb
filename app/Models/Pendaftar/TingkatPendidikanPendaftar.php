<?php

namespace App\Models\Pendaftar;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TingkatPendidikanPendaftar extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function pendidikanPendaftar()
    {
        return $this->belongsTo(PendidikanPendaftar::class, 'pendidikan_pendaftar_id');
    }
}
