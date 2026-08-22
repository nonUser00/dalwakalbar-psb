<?php

namespace App\Models\Pendaftar;

use App\Enums\StatusDokumen;
use App\Models\Auth\User;
use App\Models\Master\Dokumen;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendaftarDokumen extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pendaftar_id',
        'dokumen_id',
        'file_path',
        'status',
        'catatan',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'status' => StatusDokumen::class,
        'verified_at' => 'datetime',
    ];

    public function pendaftar()
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function dokumen()
    {
        return $this->belongsTo(Dokumen::class, 'dokumen_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
