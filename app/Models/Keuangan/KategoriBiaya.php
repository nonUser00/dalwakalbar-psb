<?php

namespace App\Models\Keuangan;

use App\Enums\JenisKategoriBiaya;
use App\Enums\JenisRombongan;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriBiaya extends Model
{
    use HasUuids;

    protected $fillable = [
        'jenis',
        'jenjang_id',
        'cabang_id',
        'jenis_rombongan',
        'name',
    ];

    protected $casts = [
        'jenis' => JenisKategoriBiaya::class,
        'jenis_rombongan' => JenisRombongan::class,
    ];

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }

    public function itemBiayas(): HasMany
    {
        return $this->hasMany(ItemBiaya::class);
    }
}
