<?php

namespace App\Http\Controllers\Psb;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Setting\Setting;
use App\Models\Ujian\KategoriPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UjianController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('psb.ujian.jadwal');
    }

    public function jadwal()
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load([
            'cabang',
            'jenjang',
            'periode',
            'gelombang',
            'kelompokUjians.pengujis',
            'kelompokUjians.koordinator',
            'penilaians.aspek',
        ]);

        $kategoriPenilaians = KategoriPenilaian::with([
            'aspek_penilaians' => function ($query) {
                $query->orderBy('urutan', 'asc');
            },
        ])->where('is_active', true)->get();

        $kontakWa = Setting::where('key', 'kontak_darurat_wa')->value('value') ?? '081234567890';
        $namaContact = Setting::where('key', 'nama_contact')->value('value') ?? 'Panitia PSB Dalwa Kalbar';
        $jamMulai = Setting::where('key', 'jam_kerja_mulai')->value('value') ?? '08:00';
        $jamSelesai = Setting::where('key', 'jam_kerja_selesai')->value('value') ?? '17:00';
        $rawHariKerja = Setting::where('key', 'hari_kerja')->value('value');
        $hariKerja = is_array($rawHariKerja) ? $rawHariKerja : (json_decode($rawHariKerja ?? '[]', true) ?: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);

        return Inertia::render('Psb/Ujian/Jadwal', [
            'pendaftar' => $pendaftar,
            'kelompokUjians' => $pendaftar->kelompokUjians,
            'kategoriPenilaians' => $kategoriPenilaians,
            'kontak' => [
                'wa' => $kontakWa,
                'nama' => $namaContact,
                'hari_kerja' => $hariKerja,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
            ],
        ]);
    }

    public function pengumuman()
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load([
            'cabang',
            'jenjang',
            'periode',
            'gelombang',
            'hasilUjian',
            'penilaians.aspek',
        ]);

        return Inertia::render('Psb/Ujian/Pengumuman', [
            'pendaftar' => $pendaftar,
            'hasilUjian' => $pendaftar->hasilUjian,
        ]);
    }
}
