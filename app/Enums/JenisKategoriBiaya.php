<?php

namespace App\Enums;

enum JenisKategoriBiaya: string
{
    case Pendaftaran = 'pendaftaran';
    case Rombongan = 'rombongan';
    case Interview = 'interview';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Pendaftaran => 'Pendaftaran & Seleksi',
            self::Rombongan => 'Rombongan & Keberangkatan',
            self::Interview => 'Interview / Tes Khusus',
            self::Lainnya => 'Biaya Lainnya',
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
