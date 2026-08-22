<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Ujian\AspekPenilaian;
use App\Models\Ujian\KategoriPenilaian;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class MasterPenilaianController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ujian.master.view', only: ['index']),
            new Middleware('permission:ujian.master.edit', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $kategoris = KategoriPenilaian::with('aspek_penilaians')->latest()->get();

        return Inertia::render('Admin/Ujian/Master/Index', [
            'kategoris' => $kategoris,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'aspeks' => 'array',
            'aspeks.*.nama_aspek' => 'required|string|max:255',
            'aspeks.*.bobot' => 'required|integer|min:1|max:100',
            'aspeks.*.indikator' => 'nullable|string',
            'aspeks.*.urutan' => 'nullable|integer',
        ]);

        $kategori = KategoriPenilaian::create([
            'nama_kategori' => $request->nama_kategori,
            'keterangan' => $request->keterangan,
            'is_active' => true,
        ]);

        if ($request->has('aspeks') && is_array($request->aspeks)) {
            foreach ($request->aspeks as $index => $aspek) {
                $kategori->aspek_penilaians()->create([
                    'nama_aspek' => $aspek['nama_aspek'],
                    'bobot' => $aspek['bobot'],
                    'indikator' => $aspek['indikator'] ?? null,
                    'urutan' => $aspek['urutan'] ?? ($index + 1),
                ]);
            }
        }

        return back()->with('success', 'Master Penilaian berhasil disimpan.');
    }

    public function update(Request $request, string $id)
    {
        $kategori = KategoriPenilaian::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'boolean',
            'aspeks' => 'array',
            'aspeks.*.id' => 'nullable|uuid',
            'aspeks.*.nama_aspek' => 'required|string|max:255',
            'aspeks.*.bobot' => 'required|integer|min:1|max:100',
            'aspeks.*.indikator' => 'nullable|string',
            'aspeks.*.urutan' => 'nullable|integer',
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
            'keterangan' => $request->keterangan,
            'is_active' => $request->is_active ?? true,
        ]);

        if ($request->has('aspeks') && is_array($request->aspeks)) {
            $existingIds = [];
            foreach ($request->aspeks as $index => $aspekData) {
                if (isset($aspekData['id'])) {
                    $aspek = AspekPenilaian::find($aspekData['id']);
                    if ($aspek && $aspek->kategori_id === $kategori->id) {
                        $aspek->update([
                            'nama_aspek' => $aspekData['nama_aspek'],
                            'bobot' => $aspekData['bobot'],
                            'indikator' => $aspekData['indikator'] ?? null,
                            'urutan' => $aspekData['urutan'] ?? ($index + 1),
                        ]);
                        $existingIds[] = $aspek->id;
                    }
                } else {
                    $newAspek = $kategori->aspek_penilaians()->create([
                        'nama_aspek' => $aspekData['nama_aspek'],
                        'bobot' => $aspekData['bobot'],
                        'indikator' => $aspekData['indikator'] ?? null,
                        'urutan' => $aspekData['urutan'] ?? ($index + 1),
                    ]);
                    $existingIds[] = $newAspek->id;
                }
            }
            // Delete aspects that were removed from the form
            $kategori->aspek_penilaians()->whereNotIn('id', $existingIds)->delete();
        } else {
            $kategori->aspek_penilaians()->delete();
        }

        return back()->with('success', 'Master Penilaian berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $kategori = KategoriPenilaian::findOrFail($id);
        $kategori->delete();

        return back()->with('success', 'Master Penilaian berhasil dihapus.');
    }
}
