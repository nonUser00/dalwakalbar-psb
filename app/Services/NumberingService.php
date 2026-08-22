<?php

namespace App\Services;

use App\Models\Setting\NumberingSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    /**
     * Generate next number for Pendaftaran.
     */
    public function generateNomorPendaftaran(): string
    {
        return $this->generate('nomor_pendaftaran');
    }

    /**
     * Generate next number for Invoice.
     */
    public function generateNomorInvoice(): string
    {
        return $this->generate('nomor_invoice');
    }

    /**
     * Generate next number for a given sequence name.
     */
    public function generate(string $sequenceName): string
    {
        return DB::transaction(function () use ($sequenceName) {
            $sequence = NumberingSequence::where('name', $sequenceName)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                return 'UNKNOWN-'.time();
            }

            // Since reset_period is removed based on user requirements,
            // we will not reset the sequence automatically anymore.
            // If the user wants a reset, they can manually edit it or we can
            // implement a specific business logic here if needed.

            $currentNumber = $sequence->next_number;

            // Format the number with padding
            $paddedNumber = str_pad($currentNumber, $sequence->padding, '0', STR_PAD_LEFT);

            // Build the final string based on pattern
            $result = $sequence->pattern;
            $result = str_replace('{PREFIX}', $sequence->prefix ?? '', $result);
            $result = str_replace('{YYYY}', $now = Carbon::now()->format('Y'), $result);
            $result = str_replace('{YY}', Carbon::now()->format('y'), $result);
            $result = str_replace('{MM}', Carbon::now()->format('m'), $result);
            $result = str_replace('{DD}', Carbon::now()->format('d'), $result);
            $result = str_replace('{AUTONUMBER}', $paddedNumber, $result);

            // Increment for next time
            $sequence->next_number = $currentNumber + 1;
            $sequence->save();

            return $result;
        });
    }
}
