<?php

namespace Database\Seeders\Ujian;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UjianPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'ujian.master.view' => 'Melihat Master Penilaian (Kategori & Aspek)',
            'ujian.master.edit' => 'Mengubah Master Penilaian',
            'ujian.kelompok.view' => 'Melihat Jadwal Kelompok Ujian',
            'ujian.kelompok.manage' => 'Mengatur Jadwal dan Anggota Kelompok',
            'ujian.penilaian.input' => 'Input Nilai Ujian',
            'ujian.hasil.finalize' => 'Finalisasi Hasil Kelulusan',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['id' => Str::uuid()]
            );
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_keys($permissions));
        }

        $pengujiRole = Role::firstOrCreate(
            ['name' => 'Penguji', 'guard_name' => 'web'],
            ['id' => Str::uuid()]
        );
        $pengujiRole->givePermissionTo(['ujian.kelompok.view', 'ujian.penilaian.input']);

        $koordinatorRole = Role::firstOrCreate(
            ['name' => 'Koordinator PSB', 'guard_name' => 'web'],
            ['id' => Str::uuid()]
        );
        $koordinatorRole->givePermissionTo([
            'ujian.master.view',
            'ujian.kelompok.view',
            'ujian.kelompok.manage',
            'ujian.penilaian.input',
            'ujian.hasil.finalize',
        ]);
    }
}
