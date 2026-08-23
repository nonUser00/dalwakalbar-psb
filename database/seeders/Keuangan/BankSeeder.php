<?php

namespace Database\Seeders\Keuangan;

use App\Models\Keuangan\Bank;
use App\Models\Keuangan\BiayaAdminBank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            [
                'kode_bank' => '002',
                'singkatan' => 'BRI',
                'name' => 'Bank Rakyat Indonesia',
                'logo_path' => 'image/bank/bri.png',
                'is_active' => true,
                'fees' => [
                    ['name' => 'System Handling', 'nominal' => 15000],
                ],
            ],
            [
                'kode_bank' => '008',
                'singkatan' => 'Mandiri',
                'name' => 'Bank Mandiri',
                'logo_path' => 'image/bank/mandiri.png',
                'is_active' => true,
                'fees' => [
                    ['name' => 'System Handling', 'nominal' => 15000],
                ],
            ],
            [
                'kode_bank' => '009',
                'singkatan' => 'BNI',
                'name' => 'Bank Negara Indonesia',
                'logo_path' => 'image/bank/bni.png',
                'is_active' => true,
                'fees' => [
                    ['name' => 'System Handling', 'nominal' => 15000],
                ],
            ],
            [
                'kode_bank' => '014',
                'singkatan' => 'BCA',
                'name' => 'Bank Central Asia',
                'logo_path' => 'image/bank/bca.png',
                'is_active' => true,
                'fees' => [
                    ['name' => 'System Handling', 'nominal' => 15000],
                ],
            ],
            [
                'kode_bank' => '451',
                'singkatan' => 'BSI',
                'name' => 'Bank Syariah Indonesia',
                'logo_path' => 'image/bank/bsi.png',
                'is_active' => true,
                'fees' => [
                    ['name' => 'System Handling', 'nominal' => 15000],
                ],
            ],
            [
                'kode_bank' => '123',
                'singkatan' => 'Bank Kalbar',
                'name' => 'Bank Pembangunan Daerah Kalimantan Barat',
                'logo_path' => 'image/bank/kalbar.png',
                'is_active' => true,
                'fees' => [
                    ['name' => 'System Handling', 'nominal' => 12500],
                    ['name' => 'Admin Bank', 'nominal' => 2500],
                ],
            ],
        ];

        foreach ($banks as $bData) {
            $bank = Bank::updateOrCreate(
                ['kode_bank' => $bData['kode_bank']],
                [
                    'singkatan' => $bData['singkatan'],
                    'name' => $bData['name'],
                    'logo_path' => $bData['logo_path'],
                    'is_active' => $bData['is_active'],
                ]
            );

            // Bersihkan data fee lama untuk bank ini agar sinkron
            BiayaAdminBank::where('bank_id', $bank->id)->delete();

            foreach ($bData['fees'] as $fData) {
                BiayaAdminBank::create([
                    'bank_id' => $bank->id,
                    'name' => $fData['name'],
                    'nominal' => $fData['nominal'],
                ]);
            }
        }
    }
}
