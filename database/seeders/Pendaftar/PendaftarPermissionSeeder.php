<?php

namespace Database\Seeders\Pendaftar;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

class PendaftarPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'pendaftar.view',
            'pendaftar.edit',
            'pendaftar.delete',
            'pendaftar.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }
}
