<?php

namespace App\Enums;

enum StatusTagihan: string
{
    case BelumBayar = 'BELUM_BAYAR';
    case BelumLunas = 'BELUM_LUNAS';
    case Lunas = 'LUNAS';
    case Samaha = 'SAMAHA';
    case Batal = 'BATAL';

    public function label(): string
    {
        return match ($this) {
            self::BelumBayar => 'Belum Bayar',
            self::BelumLunas => 'Belum Lunas',
            self::Lunas => 'Lunas',
            self::Samaha => 'Samaha (Keringanan)',
            self::Batal => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BelumBayar => 'bg-rose-100 text-rose-700 border-rose-200',
            self::BelumLunas => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Lunas => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Samaha => 'bg-sky-100 text-sky-700 border-sky-200',
            self::Batal => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
