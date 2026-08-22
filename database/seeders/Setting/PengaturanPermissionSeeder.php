<?php

namespace Database\Seeders\Setting;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

class PengaturanPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Konfigurasi Sistem
            'konfigurasi.view',
            'konfigurasi.update',

            // Log Aktivitas
            'log.view',
            'log.delete',
            'log.export',
            'log.clear',

            // Pegawai
            'pegawai.view',
            'pegawai.create',
            'pegawai.edit',
            'pegawai.delete',
            'pegawai.export',
            'pegawai.import',
            'pegawai.reset_password',
            'pegawai.update_role',
            'pegawai.toggle_status',

            // Manajemen Role
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',

            // Manajemen Permission
            'permission.view',
            'permission.create',
            'permission.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Give to Super Admin explicitly
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Give to Admin as well
        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }
}
