<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Enums\StatusPeriode;
use App\Exports\DraftPendaftarExport;
use App\Http\Controllers\Controller;
use App\Models\Master\Cabang;
use App\Models\Master\Dokumen;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftaran\Gelombang;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DraftPendaftarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pendaftar.view', only: ['index']),
            new Middleware('permission:pendaftar.edit', only: ['resetPassword']),
            new Middleware('permission:pendaftar.delete', only: ['destroy', 'bulkDestroy']),
            new Middleware('permission:pendaftar.export', only: ['export']),
        ];
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 10);
        $cabangId = $request->query('cabang_id');
        $hasExplicitGelombang = $request->has('gelombang_id');
        $gelombangId = $request->query('gelombang_id');
        $gender = $request->query('gender');
        $tipePendaftaran = $request->query('tipe_pendaftaran');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // All active Jenjangs ordered (MTs, MA, S1, S2, S3)
        $jenjangs = Jenjang::orderBy('created_at', 'asc')->get();

        // Selected active jenjang (empty by default to show all data across jenjangs)
        $selectedJenjangId = $request->query('jenjang_id');

        // Get Active Academic Year
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $hasActiveTahunAkademik = $activeTahunAkademik !== null;

        if (! $hasActiveTahunAkademik) {
            $badgeCounts = [];
            foreach ($jenjangs as $j) {
                $badgeCounts[$j->id] = 0;
            }

            return Inertia::render('Admin/Pendaftar/Draft/Index', [
                'pendaftars' => Pendaftar::whereRaw('1 = 0')->paginate($limit)->withQueryString(),
                'jenjangs' => $jenjangs,
                'jenjangCounts' => $badgeCounts,
                'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
                'cabangs' => Cabang::orderBy('name')->get(),
                'activeTahunAkademik' => null,
                'hasActiveTahunAkademik' => false,
                'gelombangs' => [],
                'masterDokumens' => Dokumen::with('jenjangs:id,name,code,singkatan')->get(),
                'filters' => [
                    'search' => (string) ($search ?? ''),
                    'limit' => $limit,
                    'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                    'cabang_id' => (string) ($cabangId ?? ''),
                    'gelombang_id' => (string) ($gelombangId ?? ''),
                    'gender' => (string) ($gender ?? ''),
                    'tipe_pendaftaran' => (string) ($tipePendaftaran ?? ''),
                    'start_date' => (string) ($startDate ?? ''),
                    'end_date' => (string) ($endDate ?? ''),
                ],
            ]);
        }

        $tahunAkademikId = $activeTahunAkademik->id;

        // Fetch Gelombangs on the active academic year
        $gelombangs = Gelombang::whereHas('periode', function ($q) use ($tahunAkademikId) {
            $q->where('tahun_akademik_id', $tahunAkademikId);
        })
            ->with('periode:id,name,status,start_date,end_date')
            ->orderBy('name')
            ->get();

        $now = now()->startOfDay();

        $gelombangsData = $gelombangs->map(function ($g) use ($now) {
            $statusVal = $g->periode?->status instanceof StatusPeriode
                ? $g->periode->status->value
                : (string) ($g->periode?->status ?? '');

            $startDate = $g->start_date ?? $g->periode?->start_date;
            $endDate = $g->end_date ?? $g->periode?->end_date;

            $isPeriodOpen = $statusVal === 'buka' || $statusVal === StatusPeriode::Buka->value;
            $isInDateRange = true;
            if ($startDate && $endDate) {
                $isInDateRange = $now->between($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay());
            } elseif ($startDate) {
                $isInDateRange = $now->greaterThanOrEqualTo($startDate->copy()->startOfDay());
            } elseif ($endDate) {
                $isInDateRange = $now->lessThanOrEqualTo($endDate->copy()->endOfDay());
            }

            $isCurrentlyOpen = $isPeriodOpen && $isInDateRange;

            return [
                'id' => $g->id,
                'name' => $g->name,
                'periode_id' => $g->periode_id,
                'periode_name' => $g->periode?->name,
                'periode_status' => $statusVal,
                'is_open' => $isPeriodOpen,
                'is_in_range' => $isInDateRange,
                'is_currently_open' => $isCurrentlyOpen,
                'start_date' => $startDate?->format('d M Y') ?? $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('d M Y') ?? $endDate?->format('Y-m-d'),
                'start_date_raw' => $startDate?->format('Y-m-d'),
                'end_date_raw' => $endDate?->format('Y-m-d'),
            ];
        });

        // Auto-select gelombang if not provided in the request
        if (! $hasExplicitGelombang) {
            $matchingWave = $gelombangsData->firstWhere('is_currently_open', true)
                ?? $gelombangsData->firstWhere('is_open', true)
                ?? $gelombangsData->first();

            if ($matchingWave) {
                $gelombangId = $matchingWave['id'];
            }
        }

        // Auto-heal any draft registrants missing periode_id or gelombang_id
        $defaultWave = $gelombangsData->firstWhere('id', $gelombangId)
            ?? $gelombangsData->firstWhere('is_currently_open', true)
            ?? $gelombangsData->firstWhere('is_open', true)
            ?? $gelombangsData->first();

        if ($defaultWave) {
            $defaultPeriodeId = $defaultWave['periode_id'];
            $defaultGelombangId = $defaultWave['id'];

            Pendaftar::where('status', 'DRAFT')
                ->where(function ($q) {
                    $q->whereNull('periode_id')->orWhereNull('gelombang_id');
                })
                ->update([
                    'periode_id' => DB::raw("COALESCE(periode_id, '{$defaultPeriodeId}')"),
                    'gelombang_id' => DB::raw("COALESCE(gelombang_id, '{$defaultGelombangId}')"),
                ]);
        }

        // Count per jenjang for draft status (strictly scoped to active academic year and selected wave)
        $jenjangCounts = [];
        foreach ($jenjangs as $j) {
            $jenjangCounts[$j->id] = Pendaftar::accessibleBy()
                ->where('status', 'DRAFT')
                ->where('jenjang_id', $j->id)
                ->where(function ($q) use ($tahunAkademikId) {
                    $q->whereHas('periode', fn ($pq) => $pq->where('tahun_akademik_id', $tahunAkademikId))
                        ->orWhereNull('periode_id');
                })
                ->when($gelombangId, function ($q) use ($gelombangId) {
                    $q->where(function ($gq) use ($gelombangId) {
                        $gq->where('gelombang_id', $gelombangId)
                            ->orWhereNull('gelombang_id');
                    });
                })
                ->count();
        }

        // Main Query (strictly scoped to active academic year)
        $query = Pendaftar::accessibleBy()
            ->where('status', 'DRAFT')
            ->where(function ($q) use ($tahunAkademikId) {
                $q->whereHas('periode', fn ($pq) => $pq->where('tahun_akademik_id', $tahunAkademikId))
                    ->orWhereNull('periode_id');
            })
            ->with(['cabang', 'jenjang', 'periode.tahunAkademik', 'gelombang', 'dokumens.dokumen', 'virtualAccounts.bank']);

        if ($selectedJenjangId) {
            $query->where('jenjang_id', $selectedJenjangId);
        }

        if ($gelombangId) {
            $query->where(function ($q) use ($gelombangId) {
                $q->where('gelombang_id', $gelombangId)
                    ->orWhereNull('gelombang_id');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                    ->orWhere('nomor_hp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        if ($gender) {
            $query->where(function ($q) use ($gender) {
                $g = strtolower($gender);
                if (str_contains($g, 'laki') || $g === 'l') {
                    $q->where('personal_data->jenis_kelamin', 'L')
                        ->orWhere('personal_data->jenis_kelamin', 'Laki-Laki')
                        ->orWhere('personal_data->jenis_kelamin', 'Laki-laki');
                } elseif (str_contains($g, 'perempuan') || $g === 'p') {
                    $q->where('personal_data->jenis_kelamin', 'P')
                        ->orWhere('personal_data->jenis_kelamin', 'Perempuan');
                }
            });
        }

        if ($tipePendaftaran) {
            $query->where('tipe_pendaftaran', $tipePendaftaran);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $pendaftars = $query->latest('created_at')->paginate($limit)->withQueryString();

        // Master options for filters
        $cabangs = Cabang::orderBy('name')->get();
        $masterDokumens = Dokumen::with('jenjangs:id,name,code,singkatan')->get();

        return Inertia::render('Admin/Pendaftar/Draft/Index', [
            'pendaftars' => $pendaftars,
            'jenjangs' => $jenjangs,
            'jenjangCounts' => $jenjangCounts,
            'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
            'cabangs' => $cabangs,
            'activeTahunAkademik' => [
                'id' => $activeTahunAkademik->id,
                'name' => $activeTahunAkademik->name,
                'is_active' => (bool) $activeTahunAkademik->is_active,
            ],
            'hasActiveTahunAkademik' => true,
            'gelombangs' => $gelombangsData,
            'masterDokumens' => $masterDokumens,
            'filters' => [
                'search' => (string) ($search ?? ''),
                'limit' => $limit,
                'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                'cabang_id' => (string) ($cabangId ?? ''),
                'gelombang_id' => (string) ($gelombangId ?? ''),
                'gender' => (string) ($gender ?? ''),
                'tipe_pendaftaran' => (string) ($tipePendaftaran ?? ''),
                'start_date' => (string) ($startDate ?? ''),
                'end_date' => (string) ($endDate ?? ''),
            ],
        ]);
    }

    public function resetPassword(Request $request, Pendaftar $pendaftar)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        DB::transaction(function () use ($pendaftar, $validated, $request) {
            $pendaftar->update([
                'password' => Hash::make($validated['password']),
            ]);

            activity()
                ->performedOn($pendaftar)
                ->causedBy(auth()->user())
                ->withProperties(['ip' => $request->ip()])
                ->log("Mereset kata sandi akun pendaftar draft {$pendaftar->nama} ({$pendaftar->nomor_pendaftaran}).");
        });

        return back()->with('success', "Kata sandi untuk pendaftar {$pendaftar->nama} berhasil diperbarui.");
    }

    public function destroy(Pendaftar $pendaftar)
    {
        DB::transaction(function () use ($pendaftar) {
            activity()
                ->performedOn($pendaftar)
                ->causedBy(auth()->user())
                ->withProperties([
                    'pendaftar_nama' => $pendaftar->nama,
                    'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                ])
                ->log("Menghapus data pendaftar draft {$pendaftar->nama} ({$pendaftar->nomor_pendaftaran}).");

            $pendaftar->delete();
        });

        return back()->with('success', "Data pendaftar draft {$pendaftar->nama} berhasil dihapus.");
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:pendaftars,id',
        ]);

        $count = count($validated['ids']);

        DB::transaction(function () use ($validated) {
            $pendaftars = Pendaftar::whereIn('id', $validated['ids'])->get();

            Pendaftar::whereIn('id', $validated['ids'])->delete();

            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'count' => count($validated['ids']),
                    'ids' => $validated['ids'],
                ])
                ->log("Menghapus massal {$pendaftars->count()} data pendaftar draft.");
        });

        return back()->with('success', "{$count} data pendaftar draft berhasil dihapus.");
    }

    public function export(Request $request): BinaryFileResponse
    {
        $ids = $request->filled('ids') ? explode(',', $request->query('ids')) : null;
        $selectedJenjangId = $request->query('jenjang_id');
        $search = $request->query('search');
        $cabangId = $request->query('cabang_id');
        $periodeId = $request->query('periode_id');
        $gelombangId = $request->query('gelombang_id');
        $gender = $request->query('gender');
        $tipePendaftaran = $request->query('tipe_pendaftaran');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $tahunAkademikId = $activeTahunAkademik?->id;

        $fileName = 'Data_Pendaftar_Draft_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new DraftPendaftarExport(
                $ids,
                $selectedJenjangId,
                $search,
                $cabangId,
                $periodeId,
                $gelombangId,
                $gender,
                $tipePendaftaran,
                $startDate,
                $endDate,
                $tahunAkademikId
            ),
            $fileName
        );
    }
}
