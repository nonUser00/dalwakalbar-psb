<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load(['roles', 'province', 'city', 'district', 'village']);

        return Inertia::render('Admin/Profile/Index', [
            'user' => $user,
            'roles' => $user->roles,
            'tab' => $request->query('tab', 'biodata'),
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if only updating photo
        $isOnlyPhoto = $request->has('foto') && count($request->except(['_token', '_method', 'foto', 'tab'])) === 0;

        if ($isOnlyPhoto) {
            $validated = $request->validate([
                'foto' => ['nullable', 'string'],
            ]);

            DB::transaction(function () use ($validated, $user, $request) {
                if (! empty($validated['foto']) && str_starts_with($validated['foto'], 'data:image')) {
                    $validated['foto'] = $this->handleBase64Image($validated['foto'], $user->foto);
                } else {
                    if ($user->foto) {
                        Storage::disk('public')->delete($user->foto);
                    }
                    $validated['foto'] = null;
                }

                $user->update(['foto' => $validated['foto']]);

                activity()
                    ->useLog('Profile')
                    ->causedBy($user)
                    ->event('avatar_updated')
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ])
                    ->log('Memperbarui foto profil akun: '.$user->name);
            });

            $msg = empty($validated['foto']) ? 'Foto profil berhasil dihapus.' : 'Foto profil berhasil diperbarui.';

            return back()->with('success', $msg);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'nik' => ['nullable', 'string', 'max:16'],
            'nip' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'string', 'in:Laki-Laki,Perempuan'],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'no_kk' => ['nullable', 'string', 'max:20'],
            'no_akta_lahir' => ['nullable', 'string', 'max:50'],
            'alamat_lengkap' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan_desa' => ['nullable', 'string', 'max:100'],
        ]);

        $oldData = $user->only([
            'name', 'email', 'nik', 'nip', 'gender', 'nomor_hp',
            'tempat_lahir', 'tanggal_lahir', 'no_kk', 'no_akta_lahir',
            'alamat_lengkap', 'rt', 'rw', 'kode_pos', 'provinsi',
            'kabupaten_kota', 'kecamatan', 'kelurahan_desa',
        ]);

        DB::transaction(function () use ($validated, $user, $request, $oldData) {
            foreach (['nik', 'nip', 'no_kk', 'no_akta_lahir', 'alamat_lengkap', 'rt', 'rw', 'kode_pos', 'provinsi', 'kabupaten_kota', 'kecamatan', 'kelurahan_desa'] as $field) {
                if (isset($validated[$field]) && empty($validated[$field])) {
                    $validated[$field] = null;
                }
            }

            $user->update($validated);

            activity()
                ->useLog('Profile')
                ->causedBy($user)
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                    'attributes' => $user->only(array_keys($oldData)),
                ])
                ->log('Memperbarui biodata profil: '.$user->name);
        });

        return back()->with('success', 'Biodata profil Anda berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        DB::transaction(function () use ($user, $validated, $request) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            activity()
                ->useLog('Auth')
                ->causedBy($user)
                ->event('password_updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Mengubah kata sandi akun');
        });

        return back()->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
    }

    private function handleBase64Image(string $base64String, ?string $oldPath = null): ?string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64String)) {
            $image_parts = explode(';base64,', $base64String);
            $image_type_aux = explode('image/', $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $fileName = 'pegawai-foto/'.uniqid().'.'.$image_type;
            Storage::disk('public')->put($fileName, $image_base64);

            return $fileName;
        }

        return $oldPath;
    }
}
