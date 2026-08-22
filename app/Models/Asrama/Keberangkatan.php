<?php

namespace App\Models\Asrama;

use App\Enums\JalurKeberangkatan;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keberangkatan extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'jalur' => JalurKeberangkatan::class,
        'tanggal_lapor' => 'datetime',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function rombongan(): BelongsTo
    {
        return $this->belongsTo(Rombongan::class);
    }
}
