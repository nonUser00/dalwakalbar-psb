<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'pendaftar_id',
        'bank_id',
        'nomor_va',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
