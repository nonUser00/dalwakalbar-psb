<?php

namespace App\Enums;

enum JalurKeberangkatan: string
{
    case Rombongan = 'ROMBONGAN';
    case Mandiri = 'MANDIRI';

    public function label(): string
    {
        return match ($this) {
            self::Rombongan => 'Rombongan Pondok',
            self::Mandiri => 'Mandiri / Keluarga',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Rombongan => 'bg-indigo-100 text-indigo-700 border-indigo-200',
            self::Mandiri => 'bg-slate-100 text-slate-700 border-slate-200',
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
