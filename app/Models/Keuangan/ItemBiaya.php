<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBiaya extends Model
{
    use HasUuids;

    protected $fillable = [
        'kategori_biaya_id',
        'name',
        'nominal',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function kategoriBiaya(): BelongsTo
    {
        return $this->belongsTo(KategoriBiaya::class);
    }
}
