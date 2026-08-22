<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Asrama\Rombongan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RombonganController extends Controller
{
    public function index()
    {
        // Require permission if configured properly
        // $this->authorize('rombongan.view');

        $rombongans = Rombongan::withCount('keberangkatans')
            ->orderBy('tanggal_berangkat', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/Pasca/Rombongan/Index', [
            'rombongans' => $rombongans,
        ]);
    }

    public function store(Request $request)
    {
        // $this->authorize('rombongan.create');

        $validated = $request->validate([
            'nama_rombongan' => 'required|string|max:255',
            'tanggal_berangkat' => 'required|date',
            'titik_kumpul' => 'required|string|max:255',
            'kuota' => 'required|integer|min:1',
            'biaya' => 'required|numeric|min:0',
            'status' => 'required|in:BUKA,PENUH,BERANGKAT,SELESAI',
        ]);

        Rombongan::create($validated);

        return redirect()->back()->with('success', 'Rombongan berhasil ditambahkan');
    }

    public function update(Request $request, Rombongan $rombongan)
    {
        // $this->authorize('rombongan.edit');

        $validated = $request->validate([
            'nama_rombongan' => 'required|string|max:255',
            'tanggal_berangkat' => 'required|date',
            'titik_kumpul' => 'required|string|max:255',
            'kuota' => 'required|integer|min:1',
            'biaya' => 'required|numeric|min:0',
            'status' => 'required|in:BUKA,PENUH,BERANGKAT,SELESAI',
        ]);

        $rombongan->update($validated);

        return redirect()->back()->with('success', 'Rombongan berhasil diperbarui');
    }

    public function destroy(Rombongan $rombongan)
    {
        // $this->authorize('rombongan.delete');

        if ($rombongan->keberangkatans()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus rombongan yang sudah memiliki pendaftar terdaftar.');
        }

        $rombongan->delete();

        return redirect()->back()->with('success', 'Rombongan berhasil dihapus');
    }
}
