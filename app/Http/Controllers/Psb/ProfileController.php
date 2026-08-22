<?php

namespace App\Http\Controllers\Psb;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(): Response
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load([
            'cabang',
            'jenjang',
            'periode.tahunAkademik',
            'gelombang',
            'dokumens.dokumen',
            'virtualAccounts.bank',
            'tagihans.tagihanItems',
            'tagihans.pembayarans',
            'kelompokUjians.pengujis',
            'kelompokUjians.koordinator',
            'hasilUjian',
            'keberangkatan',
        ]);

        return Inertia::render('Psb/Profile/Index', [
            'pendaftar' => $pendaftar,
        ]);
    }

    public function updatePassword(Request $request)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:pendaftar'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        DB::transaction(function () use ($pendaftar, $validated, $request) {
            $pendaftar->update([
                'password' => Hash::make($validated['password']),
            ]);

            activity()
                ->useLog('Pendaftar')
                ->causedBy($pendaftar)
                ->event('password_updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log("Mengubah kata sandi akun santri: {$pendaftar->nama} ({$pendaftar->nomor_pendaftaran})");
        });

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
