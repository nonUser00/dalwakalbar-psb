<?php

namespace App\Enums;

enum MetodePembayaran: string
{
    case Tunai = 'TUNAI';
    case Transfer = 'TRANSFER';
    case Samaha = 'SAMAHA';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai (Cash)',
            self::Transfer => 'Transfer Bank / Virtual Account',
            self::Samaha => 'Samaha (Potongan Beasiswa)',
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
