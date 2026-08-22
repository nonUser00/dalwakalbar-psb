<?php

namespace App\Models\Asrama;

use App\Enums\StatusRombongan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rombongan extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'status' => StatusRombongan::class,
        'tanggal_berangkat' => 'date',
        'kuota' => 'integer',
        'biaya' => 'decimal:2',
    ];

    public function keberangkatans(): HasMany
    {
        return $this->hasMany(Keberangkatan::class);
    }

    // Accessor for active slots (sisa kuota)
    public function getSisaKuotaAttribute(): int
    {
        return max(0, $this->kuota - $this->keberangkatans()->count());
    }
}
