<?php

namespace App\Enums;

enum StatusRombongan: string
{
    case Buka = 'BUKA';
    case Penuh = 'PENUH';
    case Berangkat = 'BERANGKAT';
    case Selesai = 'SELESAI';

    public function label(): string
    {
        return match ($this) {
            self::Buka => 'Buka',
            self::Penuh => 'Penuh / Kuota Habis',
            self::Berangkat => 'Dalam Perjalanan',
            self::Selesai => 'Selesai / Tiba di Tujuan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Buka => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Penuh => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Berangkat => 'bg-sky-100 text-sky-700 border-sky-200',
            self::Selesai => 'bg-slate-100 text-slate-700 border-slate-200',
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
