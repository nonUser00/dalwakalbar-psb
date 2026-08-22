<?php

namespace App\Enums;

enum StatusPeriode: string
{
    case Buka = 'buka';
    case Tutup = 'tutup';
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Buka => 'Buka',
            self::Tutup => 'Tutup',
            self::Draft => 'Draft',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Buka => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Tutup => 'bg-rose-100 text-rose-700 border-rose-200',
            self::Draft => 'bg-slate-100 text-slate-700 border-slate-200',
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
