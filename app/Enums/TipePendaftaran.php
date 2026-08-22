<?php

namespace App\Enums;

enum TipePendaftaran: string
{
    case Reguler = 'Reguler';
    case Pindahan = 'Pindahan';
    case Prestasi = 'Prestasi';
    case Lanjutan = 'Lanjutan';

    public function label(): string
    {
        return match ($this) {
            self::Reguler => 'Reguler',
            self::Pindahan => 'Pindahan',
            self::Prestasi => 'Prestasi',
            self::Lanjutan => 'Lanjutan',
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
