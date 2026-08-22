<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasUuids;

    protected $fillable = [
        'kode_bank',
        'singkatan',
        'name',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): string
    {
        if (! empty($this->logo_path)) {
            $path = trim($this->logo_path);
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            if (str_starts_with($path, 'image/') || str_starts_with($path, '/image/')) {
                return asset(ltrim($path, '/'));
            }
            if (str_starts_with($path, 'storage/') || str_starts_with($path, '/storage/')) {
                return asset(ltrim($path, '/'));
            }

            return asset('storage/'.ltrim($path, '/'));
        }

        $searchStr = strtolower(trim(($this->singkatan ?: '').' '.($this->name ?: '').' '.($this->kode_bank ?: '')));
        $code = trim($this->kode_bank ?: '');

        if (str_contains($searchStr, 'bca') || $code === '014') {
            return asset('image/bank/bca.png');
        }

        if (str_contains($searchStr, 'bni') || $code === '009') {
            return asset('image/bank/bni.png');
        }

        if (str_contains($searchStr, 'bri') || $code === '002') {
            return asset('image/bank/bri.png');
        }

        if (str_contains($searchStr, 'bsi') || str_contains($searchStr, 'syariah') || $code === '451') {
            return asset('image/bank/bsi.png');
        }

        if (str_contains($searchStr, 'mandiri') || $code === '008') {
            return asset('image/bank/mandiri.png');
        }

        if (str_contains($searchStr, 'kalbar') || $code === '123') {
            return asset('image/bank/kalbar.png');
        }

        return asset('image/bank/bri.png');
    }

    public function biayaAdmins(): HasMany
    {
        return $this->hasMany(BiayaAdminBank::class);
    }
}
