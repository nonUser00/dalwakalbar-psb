<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Enums\PendaftarStatus;
use App\Enums\StatusKelulusan;
use App\Enums\StatusPeriode;
use App\Exports\PengumumanPendaftarExport;
use App\Http\Controllers\Controller;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Ujian\HasilUjian;
use App\Models\Ujian\KategoriPenilaian;
use App\Models\Ujian\KelompokUjian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PengumumanPendaftarPageController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ujian.hasil.finalize|pendaftar.view', only: ['index', 'export']),
            new Middleware('permission:ujian.hasil.finalize|pendaftar.edit', only: ['decide', 'bulkDecide', 'reinterview', 'resetPassword']),
            new Middleware('permission:pendaftar.delete', only: ['destroy', 'bulkDestroy']),
        ];
    }

    /**
     * Display a listing of candidates for admission announcement and graduation decisions.
     */
    public function index(Request $request): Response
    {
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $selectedJenjangId = $request->input('jenjang_id');
        $cabangId = $request->input('cabang_id');
        $hasExplicitGelombang = $request->has('gelombang_id');
        $gelombangId = $request->input('gelombang_id');
        $statusKelulusan = $request->input('status_kelulusan');
        $statusPendaftar = $request->input('status');
        $gender = $request->input('gender');
        $kelompokId = $request->input('kelompok_ujian_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Master Jenjangs ordered
        $jenjangOrder = ['MTS' => 1, 'MA' => 2, 'S1' => 3, 'S2' => 4, 'S3' => 5];
        $allJenjangs = Jenjang::accessibleBy()->get()->sort(function ($a, $b) use ($jenjangOrder) {
            $orderA = $jenjangOrder[strtoupper($a->code ?? $a->singkatan ?? '')] ?? 99;
            $orderB = $jenjangOrder[strtoupper($b->code ?? $b->singkatan ?? '')] ?? 99;

            return $orderA - $orderB;
        })->values();

        // Get Active Academic Year
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $hasActiveTahunAkademik = $activeTahunAkademik !== null;

        if (! $hasActiveTahunAkademik) {
            $badgeCounts = [];
            foreach ($allJenjangs as $j) {
                $badgeCounts[$j->id] = 0;
            }

            $emptyPendaftars = Pendaftar::whereRaw('1 = 0')->paginate($limit)->withQueryString();

            return Inertia::render('Admin/Pendaftar/Pengumuman/Index', [
                'pendaftars' => $emptyPendaftars,
                'jenjangs' => $allJenjangs,
                'jenjangCounts' => $badgeCounts,
                'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
                'kategoris' => collect(),
                'cabangs' => Cabang::accessibleBy()->where('is_active', true)->select('id', 'name')->get(),
                'activeTahunAkademik' => null,
                'hasActiveTahunAkademik' => false,
                'gelombangs' => [],
                'kelompokUjians' => [],
                'filters' => [
                    'search' => (string) ($search ?? ''),
                    'limit' => $limit,
                    'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                    'cabang_id' => (string) ($cabangId ?? ''),
                    'gelombang_id' => (string) ($gelombangId ?? ''),
                    'status_kelulusan' => (string) ($statusKelulusan ?? ''),
                    'status' => (string) ($statusPendaftar ?? ''),
                    'gender' => (string) ($gender ?? ''),
                    'kelompok_ujian_id' => (string) ($kelompokId ?? ''),
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

        // Auto-select gelombang if not provided in the request:
        // Priority:
        // 1. First wave that is 'buka' and within date range (is_currently_open)
        // 2. First wave that is 'buka' (is_open)
        // 3. First wave in the active year
        if (! $hasExplicitGelombang) {
            $matchingWave = $gelombangsData->firstWhere('is_currently_open', true)
                ?? $gelombangsData->firstWhere('is_open', true)
                ?? $gelombangsData->first();

            if ($matchingWave) {
                $gelombangId = '';
            }
        }

        // Live badge counts for candidates in announcement / admission stage per jenjang (strictly for active academic year)
        $badgeCounts = [];
        foreach ($allJenjangs as $j) {
            $badgeCounts[$j->id] = Pendaftar::accessibleBy()
                ->where('jenjang_id', $j->id)
                ->whereHas('periode', function ($q) use ($tahunAkademikId) {
                    $q->where('tahun_akademik_id', $tahunAkademikId);
                })
                ->when($gelombangId, function ($q) use ($gelombangId) {
                    $q->where('gelombang_id', $gelombangId);
                })
                ->whereIn('status', [
                    PendaftarStatus::Interview,
                    PendaftarStatus::Lulus,
                    PendaftarStatus::TidakLulus,
                    PendaftarStatus::Kedatangan,
                    PendaftarStatus::Aktif,
                ])
                ->count();
        }

        // Main Query (strictly for active academic year)
        $query = Pendaftar::accessibleBy()
            ->with([
                'cabang:id,name',
                'jenjang:id,code,name,singkatan,logo_path',
                'periode:id,name,tahun_akademik_id',
                'periode.tahunAkademik:id,name',
                'gelombang:id,name',
                'kelompokUjians:id,nama_kelompok,tanggal_ujian,waktu_mulai,waktu_selesai,lokasi,status',
                'kelompokUjians.pengujis:id,name,email',
                'penilaians.aspek:id,kategori_id,nama_aspek,bobot',
                'penilaians.aspek.kategori:id,nama_kategori',
                'penilaians.penguji:id,name',
                'hasilUjian',
                'hasilUjian.dataWawancara',
                'dokumens.dokumen',
            ])
            ->whereHas('periode', function ($q) use ($tahunAkademikId) {
                $q->where('tahun_akademik_id', $tahunAkademikId);
            })
            ->whereIn('status', [
                PendaftarStatus::Interview,
                PendaftarStatus::Lulus,
                PendaftarStatus::TidakLulus,
                PendaftarStatus::Kedatangan,
                PendaftarStatus::Aktif,
            ]);

        // Filter: Jenjang
        if ($selectedJenjangId) {
            $query->where('jenjang_id', $selectedJenjangId);
        }

        // Filter: Cabang
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        // Filter: Gelombang
        if ($gelombangId) {
            $query->where('gelombang_id', $gelombangId);
        }

        // Filter: Kelompok Ujian
        if ($kelompokId) {
            $query->whereHas('kelompokUjians', function ($q) use ($kelompokId) {
                $q->where('kelompok_ujians.id', $kelompokId);
            });
        }

        // Filter: Status Kelulusan
        if ($statusKelulusan) {
            if ($statusKelulusan === 'lulus') {
                $query->where(function ($q) {
                    $q->whereHas('hasilUjian', fn ($hq) => $hq->where('status_kelulusan', StatusKelulusan::Lulus))
                        ->orWhere('status', PendaftarStatus::Lulus);
                });
            } elseif ($statusKelulusan === 'tidak_lulus') {
                $query->where(function ($q) {
                    $q->whereHas('hasilUjian', fn ($hq) => $hq->where('status_kelulusan', StatusKelulusan::TidakLulus))
                        ->orWhere('status', PendaftarStatus::TidakLulus);
                });
            }
        }

        // Filter: Status Pendaftar
        if ($statusPendaftar) {
            $query->where('status', $statusPendaftar);
        }

        // Filter: Gender
        if ($gender) {
            $g = strtolower($gender);
            if (str_contains($g, 'laki') || $g === 'l') {
                $query->where(function ($pq) {
                    $pq->where('personal_data->jenis_kelamin', 'L')
                        ->orWhere('personal_data->jenis_kelamin', 'Laki-Laki')
                        ->orWhere('personal_data->jenis_kelamin', 'Laki-laki');
                });
            } elseif (str_contains($g, 'perempuan') || $g === 'p') {
                $query->where(function ($pq) {
                    $pq->where('personal_data->jenis_kelamin', 'P')
                        ->orWhere('personal_data->jenis_kelamin', 'Perempuan');
                });
            }
        }

        // Filter: Date Range
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Filter: Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                    ->orWhere('nomor_hp', 'like', "%{$search}%");
            });
        }

        $pendaftars = $query->latest('submitted_at')->paginate($limit)->withQueryString();

        // Master Data for Options & Modals
        $kategoris = KategoriPenilaian::with(['aspek_penilaians' => function ($q) {
            $q->orderBy('urutan', 'asc')->orderBy('created_at', 'asc');
        }])->where('is_active', true)->get();

        $cabangs = Cabang::accessibleBy()->where('is_active', true)->select('id', 'name')->get();
        $kelompokUjians = KelompokUjian::with('pengujis:id,name,email')->latest()->get();

        return Inertia::render('Admin/Pendaftar/Pengumuman/Index', [
            'pendaftars' => $pendaftars,
            'jenjangs' => $allJenjangs,
            'jenjangCounts' => $badgeCounts,
            'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
            'kategoris' => $kategoris,
            'cabangs' => $cabangs,
            'activeTahunAkademik' => [
                'id' => $activeTahunAkademik->id,
                'name' => $activeTahunAkademik->name,
                'is_active' => $activeTahunAkademik->is_active,
            ],
            'hasActiveTahunAkademik' => true,
            'gelombangs' => $gelombangsData,
            'kelompokUjians' => $kelompokUjians,
            'filters' => [
                'search' => (string) ($search ?? ''),
                'limit' => $limit,
                'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                'cabang_id' => (string) ($cabangId ?? ''),
                'gelombang_id' => (string) ($gelombangId ?? ''),
                'status_kelulusan' => (string) ($statusKelulusan ?? ''),
                'status' => (string) ($statusPendaftar ?? ''),
                'gender' => (string) ($gender ?? ''),
                'kelompok_ujian_id' => (string) ($kelompokId ?? ''),
                'start_date' => (string) ($startDate ?? ''),
                'end_date' => (string) ($endDate ?? ''),
            ],
        ]);
    }

    /**
     * Decide and update admission graduation status for a candidate.
     */
    public function decide(Request $request, Pendaftar $pendaftar): RedirectResponse
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $request->validate([
            'status_kelulusan' => 'required|in:lulus,tidak_lulus',
            'catatan_final' => 'nullable|string|max:1500',
        ]);

        DB::transaction(function () use ($request, $pendaftar) {
            $statusKelulusan = $request->status_kelulusan;

            // Map pendaftar main status
            $pendaftarStatus = match ($statusKelulusan) {
                'lulus' => PendaftarStatus::Lulus,
                'tidak_lulus' => PendaftarStatus::TidakLulus,
                default => PendaftarStatus::Interview,
            };

            $pendaftar->update([
                'status' => $pendaftarStatus,
            ]);

            HasilUjian::updateOrCreate([
                'pendaftar_id' => $pendaftar->id,
            ], [
                'status_kelulusan' => match ($statusKelulusan) {
                    'lulus' => StatusKelulusan::Lulus,
                    'tidak_lulus' => StatusKelulusan::TidakLulus,
                    default => null,
                },
                'catatan_final' => $request->catatan_final ?? $pendaftar->hasilUjian?->catatan_final,
                'locked_at' => now(),
            ]);

            activity()
                ->performedOn($pendaftar)
                ->causedBy(auth()->user())
                ->withProperties([
                    'status_kelulusan' => $statusKelulusan,
                    'pendaftar_status' => $pendaftarStatus->value,
                ])
                ->log("Menetapkan keputusan kelulusan untuk calon santri {$pendaftar->nama}: {$pendaftarStatus->label()}");
        });

        return back()->with('success', "Keputusan kelulusan calon santri {$pendaftar->nama} berhasil disimpan.");
    }

    /**
     * Bulk decide admission graduation status for multiple candidates.
     */
    public function bulkDecide(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pendaftars,id',
            'status_kelulusan' => 'required|in:lulus,tidak_lulus',
            'catatan_final' => 'nullable|string|max:1500',
        ]);

        $pendaftars = Pendaftar::accessibleBy(auth()->user())->whereIn('id', $request->ids)->get();
        if ($pendaftars->isEmpty()) {
            return back()->with('error', 'Tidak ada data pendaftar yang memiliki hak akses.');
        }

        $validIds = $pendaftars->pluck('id')->toArray();

        DB::transaction(function () use ($request, $validIds) {
            $statusKelulusan = $request->status_kelulusan;
            $pendaftarStatus = match ($statusKelulusan) {
                'lulus' => PendaftarStatus::Lulus,
                'tidak_lulus' => PendaftarStatus::TidakLulus,
                default => PendaftarStatus::Interview,
            };

            Pendaftar::whereIn('id', $validIds)->update([
                'status' => $pendaftarStatus,
            ]);

            foreach ($validIds as $id) {
                HasilUjian::updateOrCreate([
                    'pendaftar_id' => $id,
                ], [
                    'status_kelulusan' => match ($statusKelulusan) {
                        'lulus' => StatusKelulusan::Lulus,
                        'tidak_lulus' => StatusKelulusan::TidakLulus,
                        default => null,
                    },
                    'catatan_final' => $request->catatan_final,
                    'locked_at' => now(),
                ]);
            }

            $count = count($validIds);
            activity()
                ->causedBy(auth()->user())
                ->log("Menetapkan kelulusan massal ({$pendaftarStatus}) untuk {$count} calon santri.");
        });

        return back()->with('success', 'Status kelulusan calon santri terpilih berhasil ditetapkan.');
    }

    /**
     * Set candidate for re-interview (Interview Ulang).
     * Changes status back to TAGIHAN, marks is_interview_ulang = true, and sets interview_ulang_at timestamp.
     * All past interview scores, groups, bills, and payment records remain intact in the database.
     */
    public function reinterview(Request $request, Pendaftar $pendaftar): RedirectResponse
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        DB::transaction(function () use ($pendaftar) {
            $pendaftar->update([
                'status' => PendaftarStatus::Tagihan,
                'is_interview_ulang' => true,
                'interview_ulang_at' => now(),
            ]);

            activity()
                ->performedOn($pendaftar)
                ->causedBy(auth()->user())
                ->withProperties([
                    'status' => PendaftarStatus::Tagihan->value,
                    'is_interview_ulang' => true,
                    'interview_ulang_at' => now()->toIso8601String(),
                ])
                ->log("Mengatur sesi interview ulang untuk calon santri {$pendaftar->nama}. Status dialihkan ke Tagihan.");
        });

        return back()->with('success', "Calon santri {$pendaftar->nama} berhasil dialihkan ke tahap Tagihan untuk sesi Interview Ulang.");
    }

    /**
     * Reset applicant password.
     */
    public function resetPassword(Request $request, Pendaftar $pendaftar): RedirectResponse
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        DB::transaction(function () use ($request, $pendaftar) {
            $pendaftar->update([
                'password' => Hash::make($request->password),
            ]);

            activity()
                ->performedOn($pendaftar)
                ->causedBy(auth()->user())
                ->log("Mereset kata sandi akun calon santri: {$pendaftar->nama} (NIK: {$pendaftar->nik})");
        });

        return back()->with('success', "Kata sandi untuk {$pendaftar->nama} berhasil diperbarui.");
    }

    /**
     * Soft delete candidate.
     */
    public function destroy(Pendaftar $pendaftar): RedirectResponse
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        DB::transaction(function () use ($pendaftar) {
            $nama = $pendaftar->nama;
            $pendaftar->delete();

            activity()
                ->performedOn($pendaftar)
                ->causedBy(auth()->user())
                ->log("Menghapus data pendaftar: {$nama}");
        });

        return back()->with('success', 'Data pendaftar berhasil dihapus.');
    }

    /**
     * Bulk delete candidates.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pendaftars,id',
        ]);

        $pendaftars = Pendaftar::accessibleBy(auth()->user())->whereIn('id', $request->ids)->get();

        if ($pendaftars->isEmpty()) {
            return back()->with('error', 'Tidak ada data pendaftar yang memiliki hak akses untuk dihapus.');
        }

        $idsToDelete = $pendaftars->pluck('id')->toArray();
        $count = count($idsToDelete);

        DB::transaction(function () use ($idsToDelete, $count) {
            Pendaftar::whereIn('id', $idsToDelete)->delete();

            activity()
                ->causedBy(auth()->user())
                ->log("Menghapus massal {$count} data pendaftar.");
        });

        return back()->with('success', 'Data pendaftar terpilih berhasil dihapus.');
    }

    /**
     * Export admission graduation and test results to Excel (.xlsx) format.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $ids = $request->filled('ids') ? explode(',', $request->query('ids')) : null;
        $selectedJenjangId = $request->query('jenjang_id');
        $search = $request->query('search');
        $cabangId = $request->query('cabang_id');
        $periodeId = $request->query('periode_id');
        $gelombangId = $request->query('gelombang_id');
        $gender = $request->query('gender');
        $statusKelulusan = $request->query('status_kelulusan');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $tahunAkademikId = $activeTahunAkademik?->id;

        $fileName = 'Data_Pengumuman_Kelulusan_Santri_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new PengumumanPendaftarExport(
                $ids,
                $selectedJenjangId,
                $search,
                $cabangId,
                $periodeId,
                $gelombangId,
                $gender,
                $statusKelulusan,
                $startDate,
                $endDate,
                $tahunAkademikId
            ),
            $fileName
        );
    }
}
