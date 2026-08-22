<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Ujian\KategoriPenilaian;
use App\Models\Ujian\KelompokUjian;
use App\Models\Ujian\Penilaian;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PenilaianController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ujian.penilaian.input'),
        ];
    }

    public function index(Request $request)
    {
        // Get kelompok assigned to the current user (penguji) or all if admin
        $user = auth()->user();
        $query = KelompokUjian::with(['pendaftars' => function ($q) {
            $q->with('penilaians');
        }]);

        if (! $user->hasRole('super-admin')) {
            $query->whereHas('pengujis', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $kelompoks = $query->latest()->get();
        $kategoris = KategoriPenilaian::with('aspek_penilaians')->where('is_active', true)->get();

        return Inertia::render('Admin/Ujian/Penilaian/Index', [
            'kelompoks' => $kelompoks,
            'kategoris' => $kategoris,
            'currentUserId' => $user->id,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kelompok_id' => 'required|exists:kelompok_ujians,id',
            'penilaians' => 'required|array',
            // penilaians format: [ pendaftar_id => [ aspek_id => nilai ] ]
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->penilaians as $pendaftar_id => $aspeks) {
                foreach ($aspeks as $aspek_id => $nilai) {
                    Penilaian::updateOrCreate(
                        [
                            'pendaftar_id' => $pendaftar_id,
                            'aspek_id' => $aspek_id,
                        ],
                        [
                            'penguji_id' => auth()->id(),
                            'kelompok_ujian_id' => $request->kelompok_id,
                            'nilai' => $nilai,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Nilai berhasil disimpan.');
    }
}
