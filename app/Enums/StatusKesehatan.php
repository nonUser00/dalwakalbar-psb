<?php

namespace App\Enums;

enum StatusKesehatan: string
{
    case Proses = 'PROSES';
    case Lulus = 'LULUS';
    case Gagal = 'GAGAL';

    public function label(): string
    {
        return match ($this) {
            self::Proses => 'Dalam Proses',
            self::Lulus => 'Lulus / Memenuhi Syarat',
            self::Gagal => 'Gagal / Tidak Memenuhi Syarat',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Proses => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Lulus => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Gagal => 'bg-rose-100 text-rose-700 border-rose-200',
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
