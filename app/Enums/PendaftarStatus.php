<?php

namespace App\Enums;

enum PendaftarStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case Tagihan = 'TAGIHAN';
    case Interview = 'INTERVIEW';
    case Lulus = 'LULUS';
    case TidakLulus = 'TIDAK_LULUS';
    case Kedatangan = 'KEDATANGAN';
    case Aktif = 'AKTIF';
    case Ditolak = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Tagihan => 'Tagihan',
            self::Interview => 'Interview',
            self::Lulus => 'Lulus',
            self::TidakLulus => 'Tidak Lulus',
            self::Kedatangan => 'Kedatangan',
            self::Aktif => 'Santri Aktif',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 border-slate-200',
            self::Submitted => 'bg-blue-100 text-blue-700 border-blue-200',
            self::Tagihan => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Interview => 'bg-indigo-100 text-indigo-700 border-indigo-200',
            self::Lulus => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::TidakLulus => 'bg-rose-100 text-rose-700 border-rose-200',
            self::Kedatangan => 'bg-purple-100 text-purple-700 border-purple-200',
            self::Aktif => 'bg-teal-100 text-teal-700 border-teal-200',
            self::Ditolak => 'bg-red-100 text-red-700 border-red-200',
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
