<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nomor_invoice',
        'pendaftar_id',
        'total_tagihan',
        'sisa_tagihan',
        'status',
        'jatuh_tempo',
    ];

    protected $casts = [
        'total_tagihan' => 'decimal:2',
        'sisa_tagihan' => 'decimal:2',
        'jatuh_tempo' => 'date',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
