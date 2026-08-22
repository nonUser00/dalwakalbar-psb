<?php

namespace Database\Seeders\Keuangan;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

class KeuanganPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'keuangan.va.view',
            'keuangan.va.create',
            'keuangan.va.import',
            'keuangan.tagihan.view',
            'keuangan.tagihan.create',
            'keuangan.pembayaran.view',
            'keuangan.pembayaran.verify',
            'keuangan.pembayaran.create_tunai',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }
}
