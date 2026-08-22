<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Master\Fakultas;
use App\Models\Master\Jenjang;
use App\Models\Master\Jurusan;
use App\Models\Master\Prodi;
use App\Models\Master\Tingkat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProgramPendidikanController extends Controller
{
    protected $models = [
        'jenjang' => Jenjang::class,
        'tingkat' => Tingkat::class,
        'jurusan' => Jurusan::class,
        'fakultas' => Fakultas::class,
        'prodi' => Prodi::class,
    ];

    public function __construct()
    {
        // Permission check
    }

    public function index()
    {
        $orderMap = ['MTS' => 1, 'MA' => 2, 'S1' => 3, 'S2' => 4, 'S3' => 5];
        $jenjangs = Jenjang::with(['tingkats', 'jurusans', 'fakultas.prodis'])
            ->get()
            ->sortBy(fn ($item) => $orderMap[strtoupper($item->code ?? '')] ?? 99)
            ->values();

        return Inertia::render('Admin/Akademik/ProgramPendidikan/Index', [
            'jenjangs' => $jenjangs,
        ]);
    }

    public function store(Request $request, $modelKey)
    {
        $this->validateModelKey($modelKey);

        $rules = ['name' => 'required|string|max:255'];

        if ($modelKey === 'jenjang') {
            $rules['code'] = 'nullable|string|max:255';
            $rules['gender_allowed'] = 'required|in:L,P,ALL';
        } elseif ($modelKey === 'tingkat' || $modelKey === 'jurusan') {
            $rules['jenjang_id'] = 'required|exists:jenjangs,id';
            if ($modelKey === 'jurusan') {
                $rules['code'] = 'required|string|max:255';
            }
            $rules['gender_allowed'] = 'required|in:L,P,ALL';
        } elseif ($modelKey === 'fakultas') {
            $rules['jenjang_id'] = 'required|exists:jenjangs,id';
            $rules['code'] = 'required|string|max:255';
        } elseif ($modelKey === 'prodi') {
            $rules['fakultas_id'] = 'required|exists:fakultas,id';
            $rules['code'] = 'required|string|max:255';
            $rules['gender_allowed'] = 'required|in:L,P,ALL';
        }

        $validated = $request->validate($rules);
        $modelClass = $this->models[$modelKey];

        DB::transaction(function () use ($modelClass, $validated, $modelKey, $request) {
            $record = $modelClass::create($validated);

            activity()
                ->useLog('Master Program Pendidikan')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Menambahkan $modelKey: ".$record->name);
        });

        return back()->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, $modelKey, $id)
    {
        $this->validateModelKey($modelKey);

        $rules = ['name' => 'required|string|max:255'];

        if ($modelKey === 'jenjang') {
            $rules['code'] = 'nullable|string|max:255';
            $rules['gender_allowed'] = 'required|in:L,P,ALL';
        } elseif ($modelKey === 'tingkat' || $modelKey === 'jurusan') {
            $rules['jenjang_id'] = 'required|exists:jenjangs,id';
            if ($modelKey === 'jurusan') {
                $rules['code'] = 'required|string|max:255';
            }
            $rules['gender_allowed'] = 'required|in:L,P,ALL';
        } elseif ($modelKey === 'fakultas') {
            $rules['jenjang_id'] = 'required|exists:jenjangs,id';
            $rules['code'] = 'required|string|max:255';
        } elseif ($modelKey === 'prodi') {
            $rules['fakultas_id'] = 'required|exists:fakultas,id';
            $rules['code'] = 'required|string|max:255';
            $rules['gender_allowed'] = 'required|in:L,P,ALL';
        }

        $validated = $request->validate($rules);
        $modelClass = $this->models[$modelKey];
        $record = $modelClass::findOrFail($id);
        $oldData = $record->toArray();

        DB::transaction(function () use ($record, $validated, $modelKey, $request, $oldData) {
            $record->update($validated);

            activity()
                ->useLog('Master Program Pendidikan')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Memperbarui $modelKey: ".$record->name);
        });

        return back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(Request $request, $modelKey, $id)
    {
        $this->validateModelKey($modelKey);
        $modelClass = $this->models[$modelKey];
        $record = $modelClass::findOrFail($id);
        $oldData = $record->toArray();

        DB::transaction(function () use ($record, $modelKey, $request, $oldData) {
            $record->delete();

            activity()
                ->useLog('Master Program Pendidikan')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                ])
                ->log("Menghapus $modelKey: ".$oldData['name']);
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
