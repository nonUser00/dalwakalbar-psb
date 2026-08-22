<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PendaftarStatus;
use App\Enums\TipePendaftaran;
use App\Http\Controllers\Controller;
use App\Models\Pendaftar\Pendaftar;
use App\Services\NumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService
    ) {}

    public function showRegistrationForm(): Response
    {
        return Inertia::render('Psb/Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'nik' => ['required', 'string', 'digits:16', 'unique:pendaftars,nik'],
            'nama' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus berjumlah 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar dalam sistem.',
            'nama.required' => 'Nama lengkap calon santri wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ]);

        $pendaftar = DB::transaction(function () use ($request) {
            $nomorPendaftaran = $this->numberingService->generateNomorPendaftaran();

            $pendaftar = Pendaftar::create([
                'nomor_pendaftaran' => $nomorPendaftaran,
                'nik' => $request->nik,
                'nama' => $request->nama,
                'password' => Hash::make($request->password),
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => TipePendaftaran::Reguler,
            ]);

            activity()
                ->useLog('Auth')
                ->causedBy($pendaftar)
                ->event('register')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Pendaftar berhasil melakukan registrasi akun');

            return $pendaftar;
        });

        Auth::guard('pendaftar')->login($pendaftar);

        return redirect()->route('psb.register.success');
    }

    public function showSuccessPage(): Response|RedirectResponse
    {
        /** @var Pendaftar|null $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        if (! $pendaftar) {
            return redirect()->route('psb.login');
        }

        return Inertia::render('Psb/Auth/RegisterSuccess', [
            'pendaftar' => [
                'id' => $pendaftar->id,
                'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                'nik' => $pendaftar->nik,
                'nama' => $pendaftar->nama,
                'status' => $pendaftar->status instanceof PendaftarStatus ? $pendaftar->status->value : (string) $pendaftar->status,
                'status_label' => $pendaftar->status instanceof PendaftarStatus ? $pendaftar->status->label() : (string) $pendaftar->status,
                'tipe_pendaftaran' => $pendaftar->tipe_pendaftaran instanceof TipePendaftaran ? $pendaftar->tipe_pendaftaran->value : ($pendaftar->tipe_pendaftaran ?? 'Reguler'),
                'created_at' => $pendaftar->created_at ? $pendaftar->created_at->translatedFormat('d F Y, H:i').' WIB' : now()->translatedFormat('d F Y, H:i').' WIB',
            ],
        ]);
    }
}
