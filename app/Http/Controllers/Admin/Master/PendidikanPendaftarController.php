<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar\PendidikanPendaftar;
use App\Models\Pendaftar\TingkatPendidikanPendaftar;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PendidikanPendaftarController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $tab = $request->input('tab', 'umum');
        $tipe = ($tab === 'pondok-pesantren' || $tab === 'Pondok Pesantren') ? 'Pondok Pesantren' : 'Umum';
        $limit = $request->input('limit', 5);

        $pendidikan = PendidikanPendaftar::with('tingkats')
            ->where('tipe', $tipe)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Admin/Master/PendidikanPendaftar/Index', [
            'pendidikan' => $pendidikan,
            'filters' => [
                'search' => $search,
                'tab' => $tab,
                'limit' => (int) $limit,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipe' => 'required|string|in:Umum,Pondok Pesantren',
            'name' => 'required|string|max:255',
        ]);

        PendidikanPendaftar::create($validated);

        return redirect()->back()->with('success', 'Data jenjang pendidikan berhasil ditambahkan.');
    }

    public function update(Request $request, PendidikanPendaftar $pendidikanPendaftar)
    {
        $validated = $request->validate([
            'tipe' => 'required|string|in:Umum,Pondok Pesantren',
            'name' => 'required|string|max:255',
        ]);

        $pendidikanPendaftar->update($validated);

        return redirect()->back()->with('success', 'Data jenjang pendidikan berhasil diperbarui.');
    }

    public function destroy(PendidikanPendaftar $pendidikanPendaftar)
    {
        $pendidikanPendaftar->delete();

        return redirect()->back()->with('success', 'Data jenjang pendidikan berhasil dihapus.');
    }

    public function storeTingkat(Request $request, PendidikanPendaftar $pendidikanPendaftar)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $pendidikanPendaftar->tingkats()->create($validated);

        return redirect()->back()->with('success', 'Sub-tingkat berhasil ditambahkan.');
    }

    public function updateTingkat(Request $request, TingkatPendidikanPendaftar $tingkat)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tingkat->update($validated);

        return redirect()->back()->with('success', 'Sub-tingkat berhasil diperbarui.');
    }

    public function destroyTingkat(TingkatPendidikanPendaftar $tingkat)
    {
        $tingkat->delete();

        return redirect()->back()->with('success', 'Sub-tingkat berhasil dihapus.');
    }
}
