<?php

namespace App\Enums;

enum StatusKelulusan: string
{
    case Lulus = 'lulus';
    case TidakLulus = 'tidak_lulus';
    case Pending = 'pending';
    case BelumDiputuskan = 'belum_diputuskan';

    public function label(): string
    {
        return match ($this) {
            self::Lulus => 'Lulus',
            self::TidakLulus => 'Tidak Lulus',
            self::Pending => 'Pending',
            self::BelumDiputuskan => 'Belum Diputuskan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Lulus => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::TidakLulus => 'bg-rose-100 text-rose-700 border-rose-200',
            self::Pending, self::BelumDiputuskan => 'bg-amber-100 text-amber-700 border-amber-200',
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
