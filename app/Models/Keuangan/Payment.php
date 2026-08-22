<?php

namespace App\Models\Keuangan;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'bank_id',
        'nominal',
        'metode',
        'bukti_path',
        'status',
        'catatan_penolakan',
        'verified_by',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
