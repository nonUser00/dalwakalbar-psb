<?php

namespace App\Models\Keuangan;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembayaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tagihan_id',
        'pendaftar_id',
        'bank_id',
        'nomor_va',
        'payment_method',
        'amount',
        'proof_path',
        'payment_date',
        'status',
        'catatan',
        'verified_by',
        'created_by',
        'verified_at',
    ];

    protected $casts = [
        'status' => StatusPembayaran::class,
        'payment_method' => MetodePembayaran::class,
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function pendaftar()
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
