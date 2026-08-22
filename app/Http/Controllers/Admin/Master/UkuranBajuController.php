<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\UkuranBaju;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UkuranBajuController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 5);

        $ukuranBaju = UkuranBaju::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Admin/Master/UkuranBaju/Index', [
            'ukuranBaju' => $ukuranBaju,
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
            $record = UkuranBaju::create($validated);

            activity()
                ->useLog('Master Data')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => $record->toArray(),
                ])
                ->log("Menambahkan Ukuran Baju: {$record->name}");
        });

        return back()->with('success', 'Data ukuran baju berhasil ditambahkan.');
    }

    public function update(Request $request, UkuranBaju $ukuranBaju)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldData = $ukuranBaju->toArray();

        DB::transaction(function () use ($validated, $ukuranBaju, $request, $oldData) {
            $ukuranBaju->update($validated);

            activity()
                ->useLog('Master Data')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name'])->toArray(),
                    'attributes' => collect($validated)->toArray(),
                ])
                ->log("Memperbarui Ukuran Baju: {$ukuranBaju->name}");
        });

        return back()->with('success', 'Data ukuran baju berhasil diperbarui.');
    }

    public function destroy(Request $request, UkuranBaju $ukuranBaju)
    {
        $oldData = $ukuranBaju->toArray();

        DB::transaction(function () use ($ukuranBaju, $request, $oldData) {
            $ukuranBaju->delete();

            activity()
                ->useLog('Master Data')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => collect($oldData)->only(['name'])->toArray(),
                ])
                ->log("Menghapus Ukuran Baju: {$oldData['name']}");
        });

        return back()->with('success', 'Data ukuran baju berhasil dihapus.');
    }
}
