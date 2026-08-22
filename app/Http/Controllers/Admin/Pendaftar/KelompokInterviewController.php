<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Ujian\KelompokUjian;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class KelompokInterviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ujian.kelompok.view', only: ['index']),
            new Middleware('permission:ujian.kelompok.manage', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = KelompokUjian::with('pengujis')->withCount('pendaftars');

        if ($request->search) {
            $query->where('nama_kelompok', 'like', '%'.$request->search.'%');
        }

        $kelompoks = $query->latest()->paginate(10);

        // Get pegawais who have 'penguji' role, or just all users for simplicity
        // Ideally we filter by role, but let's just get all users
        $pengujis = User::select('id', 'name')->get();

        // Get Pendaftars whose tagihan is fully paid (Sisa Tagihan = 0)
        // Since we don't have a direct 'lunas' status on pendaftar, we filter pendaftar who has invoices and sum of sisa is 0.
        // For simplicity now, let's fetch pendaftars that can be assigned (we might filter later or let admin select).
        $availablePendaftars = Pendaftar::select('id', 'nama', 'nomor_pendaftaran', 'nik')->latest()->get();

        return Inertia::render('Admin/Ujian/Kelompok/Index', [
            'kelompoks' => $kelompoks,
            'pengujis' => $pengujis,
            'pendaftars' => $availablePendaftars,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'penguji_id' => 'required|exists:users,id',
            'tanggal_ujian' => 'required|date',
            'waktu_mulai' => 'nullable|date_format:H:i',
            'waktu_selesai' => 'nullable|date_format:H:i',
            'lokasi' => 'nullable|string',
            'pendaftars' => 'array',
            'pendaftars.*' => 'exists:pendaftars,id',
        ]);

        $kelompok = KelompokUjian::create([
            'nama_kelompok' => $request->nama_kelompok,
            'tanggal_ujian' => $request->tanggal_ujian,
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'lokasi' => $request->lokasi,
            'status' => 'scheduled',
        ]);

        if ($request->has('penguji_id') && $request->penguji_id !== null) {
            $kelompok->pengujis()->sync($request->penguji_id);
        }

        if ($request->has('pendaftars') && ! empty($request->pendaftars)) {
            $kelompok->pendaftars()->sync($request->pendaftars);
        }

        return back()->with('success', 'Jadwal kelompok ujian berhasil dibuat.');
    }

    public function update(Request $request, string $id)
    {
        $kelompok = KelompokUjian::findOrFail($id);

        $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'penguji_id' => 'required|array',
            'penguji_id.*' => 'exists:users,id',
            'tanggal_ujian' => 'required|date',
            'waktu_mulai' => 'nullable',
            'waktu_selesai' => 'nullable',
            'lokasi' => 'nullable|string',
            'status' => 'required|string|in:scheduled,ongoing,completed',
            'pendaftars' => 'array',
        ]);

        $kelompok->update($request->except('pendaftars'));

        if ($request->has('pendaftars')) {
            $kelompok->pendaftars()->sync($request->pendaftars);
        }

        return back()->with('success', 'Jadwal kelompok ujian berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        KelompokUjian::findOrFail($id)->delete();

        return back()->with('success', 'Jadwal kelompok ujian berhasil dihapus.');
    }
}
