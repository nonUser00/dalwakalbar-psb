<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagihanItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tagihan_id',
        'item_biaya_id',
        'name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function itemBiaya()
    {
        return $this->belongsTo(ItemBiaya::class, 'item_biaya_id');
    }
}
