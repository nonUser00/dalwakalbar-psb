<?php

namespace App\Enums;

enum StatusInterview: string
{
    case BelumDijadwalkan = 'belum_dijadwalkan';
    case Terjadwal = 'terjadwal';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::BelumDijadwalkan => 'Belum Dijadwalkan',
            self::Terjadwal => 'Sudah Dijadwalkan',
            self::Selesai => 'Selesai Interview',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BelumDijadwalkan => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Terjadwal => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            self::Selesai => 'bg-blue-50 text-blue-800 border-blue-200',
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
