<?php

namespace Database\Seeders\Master;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

class MasterPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'master_referensi.view', 'master_referensi.create', 'master_referensi.edit', 'master_referensi.delete',
            'master_program.view', 'master_program.create', 'master_program.edit', 'master_program.delete',
            'master_dokumen.view', 'master_dokumen.create', 'master_dokumen.edit', 'master_dokumen.delete',
            'master_periode.view', 'master_periode.create', 'master_periode.edit', 'master_periode.delete',
            'master_keuangan.view', 'master_keuangan.create', 'master_keuangan.edit', 'master_keuangan.delete',
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
