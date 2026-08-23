<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Pendaftaran\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TahunAkademikController extends Controller
{
    public function __construct()
    {
        // Add middleware based on permissions
    }

    public function index()
    {
        $tahunAkademiks = TahunAkademik::with(['periodes.jenjangs'])->orderByDesc('name')->get();
        $jenjangs = Jenjang::accessibleBy()->orderBy('name')->get();

        return Inertia::render('Admin/Akademik/TahunAkademik/Index', [
            'tahunAkademiks' => $tahunAkademiks,
            'jenjangs' => $jenjangs,
        ]);
    }

    public function createPeriode(Request $request)
    {
        $tahunAkademiks = TahunAkademik::orderByDesc('name')->get();
        $jenjangs = Jenjang::accessibleBy()->orderBy('name')->get();

        return Inertia::render('Admin/Akademik/TahunAkademik/PeriodeForm', [
            'tahunAkademiks' => $tahunAkademiks,
            'jenjangs' => $jenjangs,
            'selectedTahunAkademikId' => $request->query('tahun_akademik_id'),
        ]);
    }

    public function editPeriode($id)
    {
        $periode = Periode::with('jenjangs')->findOrFail($id);
        $tahunAkademiks = TahunAkademik::orderByDesc('name')->get();
        $jenjangs = Jenjang::accessibleBy()->orderBy('name')->get();

        return Inertia::render('Admin/Akademik/TahunAkademik/PeriodeForm', [
            'periode' => $periode,
            'tahunAkademiks' => $tahunAkademiks,
            'jenjangs' => $jenjangs,
        ]);
    }

    public function store(Request $request, $model)
    {
        if ($model === 'tahun-akademik') {
            return $this->storeTahunAkademik($request);
        } elseif ($model === 'periode') {
            return $this->storePeriode($request);
        } elseif ($model === 'gelombang') {
            return $this->storeGelombang($request);
        }
        abort(404);
    }

    public function update(Request $request, $model, $id)
    {
        if ($model === 'tahun-akademik') {
            return $this->updateTahunAkademik($request, $id);
        } elseif ($model === 'periode') {
            return $this->updatePeriode($request, $id);
        } elseif ($model === 'gelombang') {
            return $this->updateGelombang($request, $id);
        }
        abort(404);
    }

    public function destroy(Request $request, $model, $id)
    {
        if ($model === 'tahun-akademik') {
            return $this->destroyTahunAkademik($request, $id);
        } elseif ($model === 'periode') {
            return $this->destroyPeriode($request, $id);
        } elseif ($model === 'gelombang') {
            return $this->destroyGelombang($request, $id);
        }
        abort(404);
    }

    // --- TAHUN AKADEMIK ---
    private function storeTahunAkademik(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            if ($validated['is_active']) {
                TahunAkademik::where('is_active', true)->update(['is_active' => false]);
            }
            $data = TahunAkademik::create($validated);
            activity()->useLog('Tahun Akademik')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'attributes' => collect($validated)->toArray()])
                ->log('Menambahkan Tahun Akademik: '.$data->name);
        });

        return back()->with('success', 'Tahun Akademik berhasil ditambahkan.');
    }

    private function updateTahunAkademik(Request $request, $id)
    {
        $data = TahunAkademik::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $validated, $request, $oldData) {
            if ($validated['is_active']) {
                TahunAkademik::where('is_active', true)->where('id', '!=', $data->id)->update(['is_active' => false]);
            }
            $data->update($validated);
            activity()->useLog('Tahun Akademik')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData, 'attributes' => collect($validated)->toArray()])
                ->log('Memperbarui Tahun Akademik: '.$data->name);
        });

        return back()->with('success', 'Tahun Akademik berhasil diperbarui.');
    }

    private function destroyTahunAkademik(Request $request, $id)
    {
        $data = TahunAkademik::findOrFail($id);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $request, $oldData) {
            $data->delete();
            activity()->useLog('Tahun Akademik')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log('Menghapus Tahun Akademik: '.$oldData['name']);
        });

        return back()->with('success', 'Tahun Akademik berhasil dihapus.');
    }

    // --- PERIODE ---
    private function storePeriode(Request $request)
    {
        $validated = $request->validate([
            'tahun_akademik_id' => 'required|exists:tahun_akademiks,id',
            'name' => 'required|string|max:255',
            'jalur_pendaftaran' => 'required|string|in:Semua,Reguler,Pindahan',
            'status' => 'required|string|in:buka,tutup,draft',
            'kuota' => 'nullable|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'jenjang_ids' => 'nullable|array',
            'jenjang_ids.*' => 'exists:jenjangs,id',
            'jenjang_items' => 'nullable|array',
            'jenjang_items.*.jenjang_id' => 'required_with:jenjang_items|exists:jenjangs,id',
            'jenjang_items.*.is_selected' => 'nullable|boolean',
            'jenjang_items.*.kuota' => 'nullable|integer|min:0',
        ]);

        // Validasi tidak boleh overlap range tanggal dengan periode lain pada Tahun Akademik yang sama
        $overlap = Periode::where('tahun_akademik_id', $validated['tahun_akademik_id'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_date', '<=', $validated['end_date'])
                        ->where('end_date', '>=', $validated['start_date']);
                });
            })
            ->first();

        if ($overlap) {
            return back()->withErrors([
                'start_date' => "Rentang waktu bertabrakan dengan {$overlap->name} (".
                    ($overlap->start_date?->format('d/m/Y') ?? '-').' s/d '.
                    ($overlap->end_date?->format('d/m/Y') ?? '-').'). Hanya diperbolehkan 1 gelombang dalam 1 rentang waktu.',
            ])->withInput();
        }

        DB::transaction(function () use ($validated, $request) {
            $data = Periode::create([
                'tahun_akademik_id' => $validated['tahun_akademik_id'],
                'name' => $validated['name'],
                'jalur_pendaftaran' => $validated['jalur_pendaftaran'],
                'status' => $validated['status'],
                'kuota' => $validated['kuota'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ]);

            // Sinkronkan juga model Gelombang default untuk periode ini
            Gelombang::create([
                'periode_id' => $data->id,
                'name' => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ]);

            if ($request->has('jenjang_items') && is_array($request->input('jenjang_items'))) {
                $syncData = [];
                foreach ($request->input('jenjang_items') as $item) {
                    if (! empty($item['is_selected'])) {
                        $syncData[$item['jenjang_id']] = [
                            'kuota' => (isset($item['kuota']) && $item['kuota'] !== '' && $item['kuota'] !== null) ? (int) $item['kuota'] : null,
                        ];
                    }
                }
                $data->jenjangs()->sync($syncData);
            } elseif (isset($validated['jenjang_ids'])) {
                $data->jenjangs()->sync($validated['jenjang_ids']);
            }

            activity()->useLog('Periode')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'attributes' => collect($validated)->toArray()])
                ->log('Menambahkan Periode Pendaftaran: '.$data->name);
        });

        return redirect()->route('admin.akademik.tahun_akademik.index', ['tab' => 'periode'])->with('success', 'Periode Pendaftaran berhasil ditambahkan.');
    }

    private function updatePeriode(Request $request, $id)
    {
        $data = Periode::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'jalur_pendaftaran' => 'required|string|in:Semua,Reguler,Pindahan',
            'status' => 'required|string|in:buka,tutup,draft',
            'kuota' => 'nullable|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'jenjang_ids' => 'nullable|array',
            'jenjang_ids.*' => 'exists:jenjangs,id',
            'jenjang_items' => 'nullable|array',
            'jenjang_items.*.jenjang_id' => 'required_with:jenjang_items|exists:jenjangs,id',
            'jenjang_items.*.is_selected' => 'nullable|boolean',
            'jenjang_items.*.kuota' => 'nullable|integer|min:0',
        ]);

        // Validasi tidak boleh overlap range tanggal dengan periode lain pada Tahun Akademik yang sama
        $overlap = Periode::where('tahun_akademik_id', $data->tahun_akademik_id)
            ->where('id', '!=', $data->id)
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_date', '<=', $validated['end_date'])
                        ->where('end_date', '>=', $validated['start_date']);
                });
            })
            ->first();

        if ($overlap) {
            return back()->withErrors([
                'start_date' => "Rentang waktu bertabrakan dengan {$overlap->name} (".
                    ($overlap->start_date?->format('d/m/Y') ?? '-').' s/d '.
                    ($overlap->end_date?->format('d/m/Y') ?? '-').'). Hanya diperbolehkan 1 gelombang dalam 1 rentang waktu.',
            ])->withInput();
        }

        $oldData = $data->toArray();
        $oldData['jenjang_ids'] = $data->jenjangs->pluck('id')->toArray();

        DB::transaction(function () use ($data, $validated, $request, $oldData) {
            $data->update([
                'name' => $validated['name'],
                'jalur_pendaftaran' => $validated['jalur_pendaftaran'],
                'status' => $validated['status'],
                'kuota' => $validated['kuota'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ]);

            // Sinkronkan juga gelombang terkait
            $gelombang = $data->gelombangs()->first();
            if ($gelombang) {
                $gelombang->update([
                    'name' => $validated['name'],
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                ]);
            } else {
                Gelombang::create([
                    'periode_id' => $data->id,
                    'name' => $validated['name'],
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                ]);
            }

            if ($request->has('jenjang_items') && is_array($request->input('jenjang_items'))) {
                $syncData = [];
                foreach ($request->input('jenjang_items') as $item) {
                    if (! empty($item['is_selected'])) {
                        $syncData[$item['jenjang_id']] = [
                            'kuota' => (isset($item['kuota']) && $item['kuota'] !== '' && $item['kuota'] !== null) ? (int) $item['kuota'] : null,
                        ];
                    }
                }
                $data->jenjangs()->sync($syncData);
            } elseif (isset($validated['jenjang_ids'])) {
                $data->jenjangs()->sync($validated['jenjang_ids']);
            } else {
                $data->jenjangs()->detach();
            }

            activity()->useLog('Periode')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData, 'attributes' => collect($validated)->toArray()])
                ->log('Memperbarui Periode Pendaftaran: '.$data->name);
        });

        return redirect()->route('admin.akademik.tahun_akademik.index', ['tab' => 'periode'])->with('success', 'Periode Pendaftaran berhasil diperbarui.');
    }

    private function destroyPeriode(Request $request, $id)
    {
        $data = Periode::findOrFail($id);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $request, $oldData) {
            $data->jenjangs()->detach();
            $data->delete();
            activity()->useLog('Periode')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log('Menghapus Periode Pendaftaran: '.$oldData['name']);
        });

        return redirect()->route('admin.akademik.tahun_akademik.index', ['tab' => 'periode'])->with('success', 'Periode Pendaftaran berhasil dihapus.');
    }

    // --- GELOMBANG ---
    private function storeGelombang(Request $request)
    {
        $validated = $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $periode = Periode::findOrFail($validated['periode_id']);

        // Pastikan tidak nabrak gelombang lain pada periode yang sama atau tahun akademik yang sama
        $overlap = Gelombang::whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $periode->tahun_akademik_id))
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<=', $validated['end_date'])
                    ->where('end_date', '>=', $validated['start_date']);
            })
            ->first();

        if ($overlap) {
            return back()->withErrors([
                'start_date' => "Rentang waktu gelombang bertabrakan dengan {$overlap->name} (".
                    ($overlap->start_date?->format('d/m/Y') ?? '-').' s/d '.
                    ($overlap->end_date?->format('d/m/Y') ?? '-').').',
            ])->withInput();
        }

        DB::transaction(function () use ($validated, $request) {
            $data = Gelombang::create($validated);
            activity()->useLog('Gelombang')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'attributes' => collect($validated)->toArray()])
                ->log('Menambahkan Gelombang: '.$data->name);
        });

        return back()->with('success', 'Gelombang berhasil ditambahkan.');
    }

    private function updateGelombang(Request $request, $id)
    {
        $data = Gelombang::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $periode = $data->periode;

        if ($periode) {
            $overlap = Gelombang::whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $periode->tahun_akademik_id))
                ->where('id', '!=', $data->id)
                ->where(function ($query) use ($validated) {
                    $query->where('start_date', '<=', $validated['end_date'])
                        ->where('end_date', '>=', $validated['start_date']);
                })
                ->first();

            if ($overlap) {
                return back()->withErrors([
                    'start_date' => "Rentang waktu gelombang bertabrakan dengan {$overlap->name} (".
                        ($overlap->start_date?->format('d/m/Y') ?? '-').' s/d '.
                        ($overlap->end_date?->format('d/m/Y') ?? '-').').',
                ])->withInput();
            }
        }

        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $validated, $request, $oldData) {
            $data->update($validated);
            activity()->useLog('Gelombang')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData, 'attributes' => collect($validated)->toArray()])
                ->log('Memperbarui Gelombang: '.$data->name);
        });

        return back()->with('success', 'Gelombang berhasil diperbarui.');
    }

    private function destroyGelombang(Request $request, $id)
    {
        $data = Gelombang::findOrFail($id);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $request, $oldData) {
            $data->delete();
            activity()->useLog('Gelombang')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log('Menghapus Gelombang: '.$oldData['name']);
        });

        return back()->with('success', 'Gelombang berhasil dihapus.');
    }
}
