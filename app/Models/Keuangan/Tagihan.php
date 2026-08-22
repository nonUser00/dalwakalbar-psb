<?php

namespace App\Models\Keuangan;

use App\Enums\StatusTagihan;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tagihan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nomor_invoice',
        'pendaftar_id',
        'total_amount',
        'status',
        'due_date',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'status' => StatusTagihan::class,
        'due_date' => 'date',
        'published_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function pendaftar()
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function items()
    {
        return $this->hasMany(TagihanItem::class);
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
