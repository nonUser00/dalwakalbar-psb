<?php

namespace App\Models\Master;

use App\Enums\JalurPendaftaran;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'jalur_pendaftaran',
        'is_required',
        'is_profile_photo',
    ];

    protected $casts = [
        'jalur_pendaftaran' => JalurPendaftaran::class,
        'is_required' => 'boolean',
        'is_profile_photo' => 'boolean',
    ];

    public function jenjangs()
    {
        return $this->belongsToMany(Jenjang::class, 'dokumen_jenjang', 'dokumen_id', 'jenjang_id');
    }
}
