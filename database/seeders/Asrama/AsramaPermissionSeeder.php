<?php

namespace Database\Seeders\Asrama;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AsramaPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'rombongan.view',
            'rombongan.create',
            'rombongan.edit',
            'rombongan.delete',
            'keberangkatan.view',
            'kedatangan.checkin',
            'kedatangan.kesehatan',
            'santri.sinkronisasi',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['id' => Str::uuid()]
            );
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }
}
