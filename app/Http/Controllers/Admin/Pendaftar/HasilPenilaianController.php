<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Ujian\HasilUjian;
use App\Models\Ujian\KelompokUjian;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HasilPenilaianController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ujian.hasil.finalize'),
        ];
    }

    public function index(Request $request)
    {
        $kelompoks = KelompokUjian::latest()->get();
        $query = Pendaftar::with(['penilaians.aspek.kategori', 'hasil_ujian']);

        if ($request->kelompok_id) {
            $query->whereHas('kelompok_ujians', function ($q) use ($request) {
                $q->where('kelompok_ujian_id', $request->kelompok_id);
            });
        }

        $pendaftars = $query->latest()->paginate(15);

        return Inertia::render('Admin/Ujian/Hasil/Index', [
            'pendaftars' => $pendaftars,
            'kelompoks' => $kelompoks,
            'filters' => $request->only(['kelompok_id']),
        ]);
    }

    public function finalize(Request $request)
    {
        $request->validate([
            'pendaftar_id' => 'required|exists:pendaftars,id',
            'status_kelulusan' => 'required|string|in:LULUS,GAGAL',
            'catatan_final' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $pendaftar = Pendaftar::with('penilaians.aspek')->findOrFail($request->pendaftar_id);

            // Calculate total score based on bobot
            $totalNilai = 0;
            foreach ($pendaftar->penilaians as $penilaian) {
                // simple calculation: (nilai * bobot) / 100
                $bobot = $penilaian->aspek->bobot ?? 0;
                $totalNilai += ($penilaian->nilai * $bobot) / 100;
            }

            HasilUjian::updateOrCreate(
                ['pendaftar_id' => $pendaftar->id],
                [
                    'total_nilai' => $totalNilai,
                    'status_kelulusan' => $request->status_kelulusan,
                    'catatan_final' => $request->catatan_final,
                    'locked_at' => now(),
                ]
            );

            // Update status on pendaftars table as well
            if ($request->status_kelulusan === 'LULUS') {
                $pendaftar->status = 'VERIFIED'; // Or maybe specific status LULUS
            } elseif ($request->status_kelulusan === 'GAGAL') {
                $pendaftar->status = 'REJECTED';
            }
            $pendaftar->save();
        });

        return back()->with('success', 'Hasil ujian berhasil difinalisasi.');
    }
}
