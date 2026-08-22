<?php

namespace App\Http\Controllers\Psb;

use App\Http\Controllers\Controller;
use App\Models\Asrama\Keberangkatan;
use App\Models\Asrama\Rombongan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KeberangkatanController extends Controller
{
    public function index()
    {
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load(['hasil_ujian', 'keberangkatan.rombongan']);

        // Jika belum lulus ujian, tidak boleh pilih keberangkatan
        $isLulus = $pendaftar->hasil_ujian && $pendaftar->hasil_ujian->status_kelulusan === 'LULUS';

        $rombongans = Rombongan::whereIn('status', ['BUKA', 'PENUH'])
            ->withCount('keberangkatans')
            ->orderBy('tanggal_berangkat', 'asc')
            ->get();

        return Inertia::render('Psb/Keberangkatan/Index', [
            'pendaftar' => $pendaftar,
            'rombongans' => $rombongans,
            'isLulus' => $isLulus,
        ]);
    }

    public function store(Request $request)
    {
        $pendaftar = Auth::guard('pendaftar')->user();

        $validated = $request->validate([
            'jalur' => 'required|in:ROMBONGAN,MANDIRI',
            'rombongan_id' => 'required_if:jalur,ROMBONGAN|nullable|exists:rombongans,id',
            'tanggal_lapor' => 'required_if:jalur,MANDIRI|nullable|date',
        ]);

        if ($validated['jalur'] === 'ROMBONGAN') {
            $rombongan = Rombongan::findOrFail($validated['rombongan_id']);
            if ($rombongan->sisa_kuota <= 0 && $rombongan->status === 'PENUH') {
                return redirect()->back()->withErrors(['rombongan_id' => 'Maaf, kuota rombongan ini sudah penuh.']);
            }
        }

        Keberangkatan::updateOrCreate(
            ['pendaftar_id' => $pendaftar->id],
            [
                'jalur' => $validated['jalur'],
                'rombongan_id' => $validated['jalur'] === 'ROMBONGAN' ? $validated['rombongan_id'] : null,
                'tanggal_lapor' => $validated['jalur'] === 'MANDIRI' ? $validated['tanggal_lapor'] : null,
            ]
        );

        // Jika ada logic tagihan (opsional), buat tagihan di sini jika rombongan punya biaya > 0.
        // Untuk tahap ini, kita hanya menyimpan status saja.

        return redirect()->back()->with('success', 'Pilihan keberangkatan berhasil disimpan.');
    }
}
