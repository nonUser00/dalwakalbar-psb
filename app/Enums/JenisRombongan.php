<?php

namespace App\Enums;

enum JenisRombongan: string
{
    case Pesawat = 'PESAWAT';
    case Kapal = 'KAPAL';
    case Bus = 'BUS';

    public function label(): string
    {
        return match ($this) {
            self::Pesawat => 'Pesawat Terbang',
            self::Kapal => 'Kapal Laut',
            self::Bus => 'Bus / Darat',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pesawat => 'bg-sky-100 text-sky-700 border-sky-200',
            self::Kapal => 'bg-blue-100 text-blue-700 border-blue-200',
            self::Bus => 'bg-emerald-100 text-emerald-700 border-emerald-200',
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
