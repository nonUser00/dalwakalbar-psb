<?php

namespace App\Enums;

enum GenderAllowed: string
{
    case L = 'L';
    case P = 'P';
    case All = 'ALL';

    public function label(): string
    {
        return match ($this) {
            self::L => 'Laki-Laki (Bani)',
            self::P => 'Perempuan (Banat)',
            self::All => 'Semua (Bani & Banat)',
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
