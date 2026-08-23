<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Master\Dokumen;
use App\Models\Master\Jenjang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DokumenController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 5);

        $dokumens = Dokumen::query()
            ->with('jenjangs')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('jalur_pendaftaran', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        $jenjangs = Jenjang::accessibleBy()->orderBy('name')->get();

        return Inertia::render('Admin/Akademik/Dokumen/Index', [
            'dokumens' => $dokumens,
            'jenjangs' => $jenjangs,
            'filters' => [
                'search' => $search,
                'limit' => (int) $limit,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:gambar,pdf,semua',
            'jalur_pendaftaran' => 'required|string|in:Semua,Reguler,Pindahan',
            'is_required' => 'required|boolean',
            'is_profile_photo' => 'required|boolean',
            'jenjang_ids' => 'required|array|min:1',
            'jenjang_ids.*' => 'exists:jenjangs,id',
        ]);

        // Aturan: Hanya dokumen yang mendukung gambar (gambar atau semua) yang bisa dijadikan foto profil
        if (! in_array($validated['type'], ['gambar', 'semua'])) {
            $validated['is_profile_photo'] = false;
        }

        DB::transaction(function () use ($validated, $request) {
            // Jika is_profile_photo = true, maka jadikan false yang lain (karena hanya boleh 1 foto profil)
            if ($validated['is_profile_photo']) {
                Dokumen::where('is_profile_photo', true)->update(['is_profile_photo' => false]);
            }

            $dokumen = Dokumen::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'jalur_pendaftaran' => $validated['jalur_pendaftaran'],
                'is_required' => $validated['is_required'],
                'is_profile_photo' => $validated['is_profile_photo'],
            ]);

            $dokumen->jenjangs()->sync($validated['jenjang_ids']);

            activity()
                ->useLog('Master Dokumen')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Menambahkan Dokumen Lampiran: {$dokumen->name}");
        });

        return back()->with('success', 'Dokumen lampiran pendaftaran berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $dokumen = Dokumen::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:gambar,pdf,semua',
            'jalur_pendaftaran' => 'required|string|in:Semua,Reguler,Pindahan',
            'is_required' => 'required|boolean',
            'is_profile_photo' => 'required|boolean',
            'jenjang_ids' => 'required|array|min:1',
            'jenjang_ids.*' => 'exists:jenjangs,id',
        ]);

        // Aturan: Hanya dokumen yang mendukung gambar (gambar atau semua) yang bisa dijadikan foto profil
        if (! in_array($validated['type'], ['gambar', 'semua'])) {
            $validated['is_profile_photo'] = false;
        }

        $oldData = $dokumen->toArray();
        $oldData['jenjang_ids'] = $dokumen->jenjangs->pluck('id')->toArray();

        DB::transaction(function () use ($dokumen, $validated, $request, $oldData) {
            if ($validated['is_profile_photo']) {
                Dokumen::where('is_profile_photo', true)
                    ->where('id', '!=', $dokumen->id)
                    ->update(['is_profile_photo' => false]);
            }

            $dokumen->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'jalur_pendaftaran' => $validated['jalur_pendaftaran'],
                'is_required' => $validated['is_required'],
                'is_profile_photo' => $validated['is_profile_photo'],
            ]);

            $dokumen->jenjangs()->sync($validated['jenjang_ids']);

            activity()
                ->useLog('Master Dokumen')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Memperbarui Dokumen Lampiran: {$dokumen->name}");
        });

        return back()->with('success', 'Dokumen lampiran pendaftaran berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $dokumen = Dokumen::findOrFail($id);
        $oldData = $dokumen->toArray();

        DB::transaction(function () use ($dokumen, $request, $oldData) {
            $dokumen->jenjangs()->detach();
            $dokumen->delete();

            activity()
                ->useLog('Master Dokumen')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                ])
                ->log("Menghapus Dokumen Lampiran: {$oldData['name']}");
        });

        return back()->with('success', 'Dokumen lampiran pendaftaran berhasil dihapus.');
    }
}
