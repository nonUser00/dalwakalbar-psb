<?php

namespace App\Enums;

enum JalurPendaftaran: string
{
    case Semua = 'Semua';
    case Reguler = 'Reguler';
    case Prestasi = 'Prestasi';
    case Beasiswa = 'Beasiswa';
    case Pindahan = 'Pindahan';

    public function label(): string
    {
        return match ($this) {
            self::Semua => 'Semua Jalur',
            self::Reguler => 'Reguler',
            self::Prestasi => 'Jalur Prestasi',
            self::Beasiswa => 'Jalur Beasiswa',
            self::Pindahan => 'Jalur Pindahan',
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
