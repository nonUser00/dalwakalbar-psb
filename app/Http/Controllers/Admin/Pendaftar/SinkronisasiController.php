<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SinkronisasiController extends Controller
{
    public function index(Request $request)
    {
        // Hanya pendaftar yang lulus tes kesehatan dan belum di-sinkronisasi (is_santri = false)
        // Atau sudah is_santri tapi mau diedit
        $query = Pendaftar::accessibleBy()
            ->where('status_kesehatan', 'LULUS');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                    ->orWhere('nik', 'like', "%{$request->search}%")
                    ->orWhere('nomor_pendaftaran', 'like', "%{$request->search}%");
            });
        }

        $pendaftars = $query->paginate(15);

        return Inertia::render('Admin/Pasca/Sinkronisasi/Index', [
            'pendaftars' => $pendaftars,
            'filters' => $request->only(['search']),
        ]);
    }

    public function sinkronisasi(Request $request, Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $validated = $request->validate([
            'nama_pondok' => 'required|string|max:255',
            'asrama' => 'required|string|max:255',
            'kamar' => 'required|string|max:255',
        ]);

        $pendaftar->update([
            'is_santri' => true,
            'nama_pondok' => $validated['nama_pondok'],
            'asrama' => $validated['asrama'],
            'kamar' => $validated['kamar'],
        ]);

        return redirect()->back()->with('success', 'Data pendaftar berhasil disinkronisasi menjadi Santri Aktif.');
    }
}
