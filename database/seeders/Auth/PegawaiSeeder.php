<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\Role;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $koordinatorRole = Role::firstOrCreate(['name' => 'Koordinator PSB', 'guard_name' => 'web']);
        $pengujiRole = Role::firstOrCreate(['name' => 'Penguji', 'guard_name' => 'web']);

        // Super Admin: Raihan Alqadrie
        $superAdminUser = User::firstOrCreate([
            'email' => 'raihanalqadrie2002@gmail.com',
        ], [
            'name' => 'Raihan Alqadrie',
            'password' => Hash::make('password'),
            'gender' => 'Laki-Laki',
            'tempat_lahir' => 'Pontianak',
            'tanggal_lahir' => '2002-01-15',
            'alamat_lengkap' => 'Jl. Ahmad Yani No. 12, Pontianak Selatan',
            'nip' => '199001152020011001',
            'nik' => '6171011501020001',
            'no_kk' => '6171011501020010',
            'no_akta_lahir' => '6171-LT-15012002-0001',
            'nomor_hp' => '081254321098',
        ]);

        if (! $superAdminUser->hasRole('Super Admin')) {
            $superAdminUser->assignRole($superAdminRole);
        }

        // Admin: Dr. Abdullah Ustman, M. Hi.
        $adminUser = User::firstOrCreate([
            'email' => '76binshahab@gmail.com',
        ], [
            'name' => 'Dr. Abdullah Ustman, M. Hi.',
            'password' => Hash::make('11091976'),
            'gender' => 'Laki-Laki',
            'tempat_lahir' => 'Mempawah',
            'tanggal_lahir' => '1976-09-11',
            'alamat_lengkap' => 'Jl. Raya Mempawah No. 45, Mempawah',
            'nip' => '197609112010011002',
            'nik' => '6171011109760002',
            'no_kk' => '6171011109760020',
            'no_akta_lahir' => '6171-LT-11091976-0002',
            'nomor_hp' => '081398765432',
        ]);

        if (! $adminUser->hasRole('Admin')) {
            $adminUser->assignRole($adminRole);
        }
        if (! $adminUser->hasRole('Koordinator PSB')) {
            $adminUser->assignRole($koordinatorRole);
        }

        // Koordinator PSB: Ust. Ahmad Fauzi, M.Pd.
        $koordinatorUser = User::firstOrCreate([
            'email' => 'ahmad.fauzi@dalwa.ac.id',
        ], [
            'name' => 'Ust. Ahmad Fauzi, M.Pd.',
            'password' => Hash::make('password'),
            'gender' => 'Laki-Laki',
            'tempat_lahir' => 'Pontianak',
            'tanggal_lahir' => '1985-05-12',
            'alamat_lengkap' => 'Jl. Danau Sentarum No. 88, Pontianak Kota',
            'nip' => '198505122015011003',
            'nik' => '6171011205850003',
            'nomor_hp' => '081299887711',
        ]);

        if (! $koordinatorUser->hasRole('Koordinator PSB')) {
            $koordinatorUser->assignRole($koordinatorRole);
        }

        // Penguji 1: Ust. Muhammad Sholeh, Lc.
        $penguji1 = User::firstOrCreate([
            'email' => 'm.sholeh@dalwa.ac.id',
        ], [
            'name' => 'Ust. Muhammad Sholeh, Lc.',
            'password' => Hash::make('password'),
            'gender' => 'Laki-Laki',
            'tempat_lahir' => 'Kubu Raya',
            'tanggal_lahir' => '1988-08-20',
            'alamat_lengkap' => 'Jl. Sungai Raya Dalam No. 15, Kubu Raya',
            'nip' => '198808202018011004',
            'nik' => '6112012008880004',
            'nomor_hp' => '081388776622',
        ]);

        if (! $penguji1->hasRole('Penguji')) {
            $penguji1->assignRole($pengujiRole);
        }

        // Penguji 2: Ustadzah Siti Fatimah, S.Pd.I.
        $penguji2 = User::firstOrCreate([
            'email' => 'siti.fatimah@dalwa.ac.id',
        ], [
            'name' => 'Ustadzah Siti Fatimah, S.Pd.I.',
            'password' => Hash::make('password'),
            'gender' => 'Perempuan',
            'tempat_lahir' => 'Singkawang',
            'tanggal_lahir' => '1992-11-03',
            'alamat_lengkap' => 'Jl. Alianyang No. 22, Singkawang Barat',
            'nip' => '199211032019012005',
            'nik' => '6172010311920005',
            'nomor_hp' => '081577665533',
        ]);

        if (! $penguji2->hasRole('Penguji')) {
            $penguji2->assignRole($pengujiRole);
        }
    }
}
