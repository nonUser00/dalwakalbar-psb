<?php

namespace App\Enums;

enum StatusDokumen: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak / Perlu Revisi',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Approved => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Rejected => 'bg-rose-100 text-rose-700 border-rose-200',
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
