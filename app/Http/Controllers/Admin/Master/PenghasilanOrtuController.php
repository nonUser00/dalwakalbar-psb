<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\PenghasilanOrtu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PenghasilanOrtuController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 5);

        $penghasilanOrtu = PenghasilanOrtu::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Admin/Master/PenghasilanOrtu/Index', [
            'penghasilanOrtu' => $penghasilanOrtu,
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
            $record = PenghasilanOrtu::create($validated);

            activity()
                ->useLog('Master Data')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => $record->toArray(),
                ])
                ->log("Menambahkan Penghasilan Orang Tua: {$record->name}");
        });

        return back()->with('success', 'Data penghasilan orang tua berhasil ditambahkan.');
    }

    public function update(Request $request, PenghasilanOrtu $penghasilanOrtu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldData = $penghasilanOrtu->toArray();

        DB::transaction(function () use ($validated, $penghasilanOrtu, $request, $oldData) {
            $penghasilanOrtu->update($validated);

            activity()
                ->useLog('Master Data')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name'])->toArray(),
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Memperbarui Penghasilan Orang Tua: {$penghasilanOrtu->name}");
        });

        return back()->with('success', 'Data penghasilan orang tua berhasil diperbarui.');
    }

    public function destroy(Request $request, PenghasilanOrtu $penghasilanOrtu)
    {
        $oldData = $penghasilanOrtu->toArray();

        DB::transaction(function () use ($penghasilanOrtu, $request, $oldData) {
            $penghasilanOrtu->delete();

            activity()
                ->useLog('Master Data')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name'])->toArray(),
                ])
                ->log("Menghapus Penghasilan Orang Tua: {$oldData['name']}");
        });

        return back()->with('success', 'Data penghasilan orang tua berhasil dihapus.');
    }
}
