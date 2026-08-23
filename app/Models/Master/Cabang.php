<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function scopeAccessibleBy($query, $user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        $allowedCabang = $user->allowed_cabang_ids;
        if (is_array($allowedCabang)) {
            if (empty($allowedCabang)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('id', $allowedCabang);
        }

        return $query;
    }
}
