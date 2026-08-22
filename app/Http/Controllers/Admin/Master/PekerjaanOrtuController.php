<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\PekerjaanOrtu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PekerjaanOrtuController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 5);

        $pekerjaanOrtu = PekerjaanOrtu::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        $existingLainnya = PekerjaanOrtu::where('is_lainnya', true)->first(['id', 'name']);

        return Inertia::render('Admin/Master/PekerjaanOrtu/Index', [
            'pekerjaanOrtu' => $pekerjaanOrtu,
            'hasLainnya' => $existingLainnya ? $existingLainnya->id : null,
            'filters' => [
                'search' => $search,
                'limit' => (int) $limit,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required_without:is_lainnya|nullable|string|max:255',
            'is_lainnya' => 'boolean',
        ]);

        $isLainnya = ! empty($validated['is_lainnya']);

        if ($isLainnya) {
            // Pastikan belum ada Pekerjaan Lainnya
            $exists = PekerjaanOrtu::where('is_lainnya', true)->exists();
            if ($exists) {
                return back()->withErrors(['is_lainnya' => 'Opsi Pekerjaan Lainnya sudah ada di database (hanya boleh ada 1).']);
            }
            $validated['name'] = 'Pekerjaan Lainnya';
        }

        DB::transaction(function () use ($validated, $request) {
            $record = PekerjaanOrtu::create([
                'name' => $validated['name'],
                'is_lainnya' => ! empty($validated['is_lainnya']),
            ]);

            activity()
                ->useLog('Master Data')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => $record->toArray(),
                ])
                ->log("Menambahkan Pekerjaan Orang Tua: {$record->name}");
        });

        return back()->with('success', 'Data pekerjaan orang tua berhasil ditambahkan.');
    }

    public function update(Request $request, PekerjaanOrtu $pekerjaanOrtu)
    {
        $validated = $request->validate([
            'name' => 'required_without:is_lainnya|nullable|string|max:255',
            'is_lainnya' => 'boolean',
        ]);

        $isLainnya = ! empty($validated['is_lainnya']);

        if ($isLainnya) {
            // Pastikan tidak ada Pekerjaan Lainnya selain record ini sendiri
            $exists = PekerjaanOrtu::where('is_lainnya', true)
                ->where('id', '!=', $pekerjaanOrtu->id)
                ->exists();

            if ($exists) {
                return back()->withErrors(['is_lainnya' => 'Opsi Pekerjaan Lainnya sudah dikonfigurasi pada data lain.']);
            }
            $validated['name'] = 'Pekerjaan Lainnya';
        }

        $oldData = $pekerjaanOrtu->toArray();

        DB::transaction(function () use ($validated, $pekerjaanOrtu, $request, $oldData) {
            $pekerjaanOrtu->update([
                'name' => $validated['name'],
                'is_lainnya' => ! empty($validated['is_lainnya']),
            ]);

            activity()
                ->useLog('Master Data')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name', 'is_lainnya'])->toArray(),
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Memperbarui Pekerjaan Orang Tua: {$pekerjaanOrtu->name}");
        });

        return back()->with('success', 'Data pekerjaan orang tua berhasil diperbarui.');
    }

    public function destroy(Request $request, PekerjaanOrtu $pekerjaanOrtu)
    {
        $oldData = $pekerjaanOrtu->toArray();

        DB::transaction(function () use ($pekerjaanOrtu, $request, $oldData) {
            $pekerjaanOrtu->delete();

            activity()
                ->useLog('Master Data')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name', 'is_lainnya'])->toArray(),
                ])
                ->log("Menghapus Pekerjaan Orang Tua: {$oldData['name']}");
        });

        return back()->with('success', 'Data pekerjaan orang tua berhasil dihapus.');
    }
}
