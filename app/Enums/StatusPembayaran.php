<?php

namespace App\Enums;

enum StatusPembayaran: string
{
    case MenungguVerifikasi = 'MENUNGGU_VERIFIKASI';
    case Diterima = 'DITERIMA';
    case Ditolak = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Diterima => 'Diterima / Terverifikasi',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::MenungguVerifikasi => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Diterima => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Ditolak => 'bg-rose-100 text-rose-700 border-rose-200',
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
