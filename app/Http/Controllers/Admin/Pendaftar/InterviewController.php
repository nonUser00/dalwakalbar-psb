<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Ujian\KelompokUjian;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InterviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pendaftar.edit', only: ['create', 'store']),
        ];
    }

    public function create(Request $request)
    {
        // Selected pendaftars from bulk action
        $pendaftarIds = $request->query('ids', []);

        $pendaftars = Pendaftar::whereIn('id', $pendaftarIds)
            ->with(['program', 'gelombang'])
            ->get();

        $pengujis = User::permission('interview.score')->get(); // Assume penguji has this permission, or just all Pegawai

        return Inertia::render('Admin/Interview/Create', [
            'pendaftars' => $pendaftars,
            'pengujis' => $pengujis,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'penguji_ids' => 'required|array|min:1',
            'penguji_ids.*' => 'exists:users,id',
            'koordinator_ids' => 'nullable|array',
            'koordinator_ids.*' => 'exists:users,id',
            'pengawas_ids' => 'nullable|array',
            'pengawas_ids.*' => 'exists:users,id',
            'tanggal_ujian' => 'required|date',
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required',
            'lokasi' => 'required|string',
            'pendaftar_ids' => 'required|array|min:1',
            'pendaftar_ids.*' => 'exists:pendaftars,id',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $kelompok = KelompokUjian::create([
                'nama_kelompok' => $validated['nama_kelompok'],
                'tanggal_ujian' => $validated['tanggal_ujian'],
                'waktu_mulai' => $validated['waktu_mulai'],
                'waktu_selesai' => $validated['waktu_selesai'],
                'lokasi' => $validated['lokasi'],
                'status' => 'DRAFT', // Can be changed later
            ]);

            // Attach pengujis
            $kelompok->pengujis()->sync($validated['penguji_ids']);

            // Attach koordinator
            $koordinatorIds = $validated['koordinator_ids'] ?? $validated['pengawas_ids'] ?? [];
            if (! empty($koordinatorIds)) {
                $kelompok->koordinator()->sync($koordinatorIds);
            }

            // Attach pendaftars
            $kelompok->pendaftars()->attach($validated['pendaftar_ids']);

            // Update pendaftars status to INTERVIEW
            Pendaftar::whereIn('id', $validated['pendaftar_ids'])->update(['status' => 'INTERVIEW']);

            activity()->useLog('Pendaftaran')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()])
                ->log("Membuat jadwal interview {$kelompok->nama_kelompok} untuk ".count($validated['pendaftar_ids']).' pendaftar');
        });

        return redirect()->route('admin.pendaftar.index', ['status' => 'set_interview'])
            ->with('success', 'Jadwal interview berhasil dibuat.');
    }
}
