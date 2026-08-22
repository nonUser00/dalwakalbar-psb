<?php

namespace App\Http\Controllers\Admin\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Setting\NumberingSequence;
use App\Models\Setting\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:konfigurasi.view', only: ['index']),
            new Middleware('permission:konfigurasi.update', only: ['updateGeneral', 'updateKopSurat', 'updateSequence']),
        ];
    }

    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        $sequences = NumberingSequence::all();

        return Inertia::render('Admin/Pengaturan/KonfigurasiSistem/Index', [
            'settings' => $settings,
            'sequences' => $sequences,
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'kontak_darurat_wa' => 'required|string|max:255',
            'nama_contact' => 'required|string|max:255',
            'hari_kerja' => 'nullable|array',
            'hari_kerja.*' => 'string',
            'jam_kerja_mulai' => 'nullable|string|max:10',
            'jam_kerja_selesai' => 'nullable|string|max:10',
        ]);

        foreach ($validated as $key => $value) {
            $val = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $val, 'group' => 'umum', 'type' => is_array($value) ? 'json' : 'string']
            );
        }

        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Pengaturan umum berhasil disimpan.');
    }

    public function updateKopSurat(Request $request)
    {
        $request->validate([
            'kop_surat' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('kop_surat')) {
            $path = $request->file('kop_surat')->store('settings', 'public');

            $setting = Setting::where('key', 'kop_surat_path')->first();

            if ($setting && $setting->value) {
                Storage::disk('public')->delete($setting->value);
            }

            Setting::updateOrCreate(
                ['key' => 'kop_surat_path'],
                ['value' => $path, 'group' => 'cetak', 'type' => 'image']
            );

            Cache::forget('app_settings');
        }

        return redirect()->back()->with('success', 'Kop surat berhasil diperbarui.');
    }

    public function updateSequence(Request $request, NumberingSequence $sequence)
    {
        $validated = $request->validate([
            'prefix' => 'nullable|string|max:50',
            'pattern' => 'required|string|max:255',
            'padding' => 'required|integer|min:1|max:10',
        ]);

        $sequence->update($validated);

        return redirect()->back()->with('success', 'Format penomoran berhasil diperbarui.');
    }
}
