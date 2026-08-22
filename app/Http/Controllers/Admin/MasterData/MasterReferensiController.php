<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Master\Cabang;
use App\Models\Master\PekerjaanOrtu;
use App\Models\Master\PendidikanOrtu;
use App\Models\Master\PenghasilanOrtu;
use App\Models\Master\UkuranBaju;
use App\Models\Pendaftar\PendidikanPendaftar;
use App\Models\Pendaftar\TingkatPendidikanPendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MasterReferensiController extends Controller
{
    protected $models = [
        'cabang' => Cabang::class,
        'ukuran_baju' => UkuranBaju::class,
        'pendidikan_ortu' => PendidikanOrtu::class,
        'pekerjaan_ortu' => PekerjaanOrtu::class,
        'pendidikan_pendaftar' => PendidikanPendaftar::class,
        'tingkat_pendidikan_pendaftar' => TingkatPendidikanPendaftar::class,
        'penghasilan_ortu' => PenghasilanOrtu::class,
    ];

    public function __construct()
    {
        // Permission check can be disabled for now if not needed, or handled via middleware
        // $this->middleware('permission:master_referensi.view')->only(['index']);
        // $this->middleware('permission:master_referensi.create')->only(['store']);
        // $this->middleware('permission:master_referensi.edit')->only(['update']);
        // $this->middleware('permission:master_referensi.delete')->only(['destroy']);
    }

    public function index()
    {
        return Inertia::render('Admin/Master/Referensi/Index', [
            'cabang' => Cabang::orderBy('name')->get(),
            'ukuran_baju' => UkuranBaju::orderBy('name')->get(),
            'pendidikan_ortu' => PendidikanOrtu::orderBy('name')->get(),
            'pekerjaan_ortu' => PekerjaanOrtu::orderBy('name')->get(),
            'pendidikan_pendaftar' => PendidikanPendaftar::with('tingkats')->orderBy('name')->get(),
            'penghasilan_ortu' => PenghasilanOrtu::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, $modelKey)
    {
        $this->validateModelKey($modelKey);

        $rules = ['name' => 'required|string|max:255'];
        if ($modelKey === 'pekerjaan_ortu') {
            $rules['is_lainnya'] = 'boolean';
        } elseif ($modelKey === 'tingkat_pendidikan_pendaftar') {
            $rules['pendidikan_pendaftar_id'] = 'required|exists:pendidikan_pendaftars,id';
        }

        $validated = $request->validate($rules);
        $modelClass = $this->models[$modelKey];

        DB::transaction(function () use ($modelKey, $validated, $modelClass, $request) {
            if ($modelKey === 'pekerjaan_ortu' && ! empty($validated['is_lainnya'])) {
                PekerjaanOrtu::where('is_lainnya', true)->update(['is_lainnya' => false]);
            }

            $record = $modelClass::create($validated);

            activity()
                ->useLog('Master Referensi')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Menambahkan {$modelKey}: ".$record->name);
        });

        return back()->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, $modelKey, $id)
    {
        $this->validateModelKey($modelKey);

        $rules = ['name' => 'required|string|max:255'];
        if ($modelKey === 'pekerjaan_ortu') {
            $rules['is_lainnya'] = 'boolean';
        } elseif ($modelKey === 'tingkat_pendidikan_pendaftar') {
            $rules['pendidikan_pendaftar_id'] = 'required|exists:pendidikan_pendaftars,id';
        }

        $validated = $request->validate($rules);
        $modelClass = $this->models[$modelKey];
        $record = $modelClass::findOrFail($id);

        $oldData = collect($record->toArray())->only(['name', 'is_lainnya', 'pendidikan_pendaftar_id'])->toArray();

        DB::transaction(function () use ($modelKey, $validated, $record, $request, $id, $oldData) {
            if ($modelKey === 'pekerjaan_ortu' && ! empty($validated['is_lainnya'])) {
                PekerjaanOrtu::where('is_lainnya', true)->where('id', '!=', $id)->update(['is_lainnya' => false]);
            }

            $record->update($validated);

            activity()
                ->useLog('Master Referensi')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Memperbarui {$modelKey}: ".$record->name);
        });

        return back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy($modelKey, $id, Request $request)
    {
        $this->validateModelKey($modelKey);
        $modelClass = $this->models[$modelKey];
        $record = $modelClass::findOrFail($id);

        $oldData = collect($record->toArray())->only(['name'])->toArray();

        DB::transaction(function () use ($record, $modelKey, $request, $oldData) {
            $record->delete();

            activity()
                ->useLog('Master Referensi')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                ])
                ->log("Menghapus {$modelKey}: ".$oldData['name']);
        });

        return back()->with('success', 'Data berhasil dihapus.');
    }

    protected function validateModelKey($modelKey)
    {
        if (! array_key_exists($modelKey, $this->models)) {
            abort(404, 'Model not found.');
        }
    }
}
