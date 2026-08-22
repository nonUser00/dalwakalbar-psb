<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\PendidikanOrtu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PendidikanOrtuController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 5);

        $pendidikanOrtu = PendidikanOrtu::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Admin/Master/PendidikanOrtu/Index', [
            'pendidikanOrtu' => $pendidikanOrtu,
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
        ]);

        DB::transaction(function () use ($validated, $request) {
            $record = PendidikanOrtu::create($validated);

            activity()
                ->useLog('Master Data')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => $record->toArray(),
                ])
                ->log("Menambahkan Pendidikan Terakhir Orang Tua: {$record->name}");
        });

        return back()->with('success', 'Data pendidikan terakhir orang tua berhasil ditambahkan.');
    }

    public function update(Request $request, PendidikanOrtu $pendidikanOrtu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldData = $pendidikanOrtu->toArray();

        DB::transaction(function () use ($validated, $pendidikanOrtu, $request, $oldData) {
            $pendidikanOrtu->update($validated);

            activity()
                ->useLog('Master Data')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name'])->toArray(),
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Memperbarui Pendidikan Terakhir Orang Tua: {$pendidikanOrtu->name}");
        });

        return back()->with('success', 'Data pendidikan terakhir orang tua berhasil diperbarui.');
    }

    public function destroy(Request $request, PendidikanOrtu $pendidikanOrtu)
    {
        $oldData = $pendidikanOrtu->toArray();

        DB::transaction(function () use ($pendidikanOrtu, $request, $oldData) {
            $pendidikanOrtu->delete();

            activity()
                ->useLog('Master Data')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name'])->toArray(),
                ])
                ->log("Menghapus Pendidikan Terakhir Orang Tua: {$oldData['name']}");
        });

        return back()->with('success', 'Data pendidikan terakhir orang tua berhasil dihapus.');
    }
}
