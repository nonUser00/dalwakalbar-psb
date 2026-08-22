<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'item_biaya_id',
        'nama_item',
        'nominal',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function itemBiaya(): BelongsTo
    {
        return $this->belongsTo(ItemBiaya::class);
    }
}
