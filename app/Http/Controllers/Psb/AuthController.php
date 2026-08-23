<?php

namespace App\Http\Controllers\Psb;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Setting\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $wa = Setting::where('key', 'kontak_darurat_wa')->value('value') ?? 'Hubungi Panitia';

        return Inertia::render('Psb/Auth/Login', [
            'kontak_darurat_wa' => $wa,
        ]);
    }

    public function showForgotPasswordForm()
    {
        $wa = Setting::where('key', 'kontak_darurat_wa')->value('value') ?? 'Hubungi Panitia';

        return Inertia::render('Psb/Auth/ForgotPassword', [
            'kontak_darurat_wa' => $wa,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // PSB Authentication Logic
        // For now, allow a basic mock login or login via web guard if using the same users table
        // But since Pendaftar is a separate model, we should use a custom guard or custom check
        // We will implement a custom check since auth scaffold for multi-guard is not fully detailed here.

        $pendaftar = Pendaftar::where('nik', $request->identifier)
            ->orWhere('nomor_pendaftaran', $request->identifier)
            ->orWhere('email', $request->identifier)
            ->first();

        if ($pendaftar && \Hash::check($request->password, $pendaftar->password)) {
            Auth::guard('pendaftar')->login($pendaftar, $request->boolean('remember'));
            $request->session()->regenerate();

            activity()
                ->useLog('Auth')
                ->causedBy($pendaftar)
                ->event('login')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'nama' => $pendaftar->nama,
                    'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                    'nik' => $pendaftar->nik,
                ])
                ->log("Pendaftar {$pendaftar->nama} ({$pendaftar->nomor_pendaftaran}) berhasil login");

            $intendedUrl = $request->session()->pull('url.intended');
            if ($intendedUrl && str_starts_with(parse_url($intendedUrl, PHP_URL_PATH) ?? '', '/psb') && ! str_contains($intendedUrl, '/psb/login') && ! str_contains($intendedUrl, '/psb/register')) {
                return redirect()->to($intendedUrl);
            }

            return redirect()->route('psb.dashboard');
        }

        return back()->withErrors([
            'identifier' => 'NIK / No. Pendaftaran atau Password salah.',
        ])->onlyInput('identifier');
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('pendaftar')->user();
        if ($user) {
            activity()
                ->useLog('Auth')
                ->causedBy($user)
                ->event('logout')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'nama' => $user->nama,
                    'nomor_pendaftaran' => $user->nomor_pendaftaran,
                    'nik' => $user->nik,
                ])
                ->log("Pendaftar {$user->nama} ({$user->nomor_pendaftaran}) berhasil logout");
        }

        Auth::guard('pendaftar')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
