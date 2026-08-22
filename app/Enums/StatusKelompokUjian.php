<?php

namespace App\Enums;

enum StatusKelompokUjian: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Terjadwal',
            self::InProgress => 'Sedang Berlangsung',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-sky-100 text-sky-700 border-sky-200',
            self::InProgress => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Completed => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Cancelled => 'bg-rose-100 text-rose-700 border-rose-200',
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
