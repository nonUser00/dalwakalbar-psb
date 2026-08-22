<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiayaAdminBank extends Model
{
    use HasUuids;

    protected $fillable = [
        'bank_id',
        'name',
        'nominal',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
