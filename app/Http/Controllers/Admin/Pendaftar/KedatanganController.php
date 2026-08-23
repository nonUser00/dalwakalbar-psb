<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KedatanganController extends Controller
{
    public function index(Request $request)
    {
        // Pendaftar yang minimal punya keberangkatan (sudah lapor berangkat/rombongan)
        $query = Pendaftar::accessibleBy()
            ->with(['keberangkatan.rombongan'])
            ->where(function ($q) {
                $q->whereHas('kelulusan', function ($sq) {
                    $sq->where('status_kelulusan', 'LULUS');
                })->orWhereHas('hasil_ujian', function ($sq) {
                    $sq->where('status_kelulusan', 'LULUS');
                });
            });

        // Search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                    ->orWhere('nik', 'like', "%{$request->search}%")
                    ->orWhere('nomor_pendaftaran', 'like', "%{$request->search}%");
            });
        }

        $pendaftars = $query->paginate(15);

        return Inertia::render('Admin/Pasca/Kedatangan/Index', [
            'pendaftars' => $pendaftars,
            'filters' => $request->only(['search']),
        ]);
    }

    public function updateKesehatan(Request $request, Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $validated = $request->validate([
            'status_kesehatan' => 'required|in:PROSES,LULUS,GAGAL',
            'catatan_kesehatan' => 'nullable|string',
        ]);

        $pendaftar->update([
            'status_kesehatan' => $validated['status_kesehatan'],
            'catatan_kesehatan' => $validated['catatan_kesehatan'],
        ]);

        return redirect()->back()->with('success', 'Status kesehatan berhasil diperbarui.');
    }
}
