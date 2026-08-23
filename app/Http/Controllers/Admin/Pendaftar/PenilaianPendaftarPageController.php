<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Enums\PendaftarStatus;
use App\Enums\StatusKelompokUjian;
use App\Enums\StatusKelulusan;
use App\Enums\StatusPeriode;
use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Setting\Setting;
use App\Models\Ujian\AspekPenilaian;
use App\Models\Ujian\HasilUjian;
use App\Models\Ujian\HasilWawancara;
use App\Models\Ujian\KategoriPenilaian;
use App\Models\Ujian\KelompokUjian;
use App\Models\Ujian\Penilaian;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenilaianPendaftarPageController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ujian.penilaian.input|pendaftar.view', only: ['index', 'spreadsheet', 'export', 'cetakSuratHasil', 'editKelompok', 'showKelompok']),
            new Middleware('permission:ujian.penilaian.input|pendaftar.edit', only: ['updateKelompok', 'storeScore', 'storeInterviewNote', 'storePenentuanKelas', 'storeKelulusan', 'finalize', 'bulkFinalize', 'unlock', 'lockKelompok', 'unlockKelompok', 'resetPassword']),
            new Middleware('permission:ujian.penilaian.input|pendaftar.delete', only: ['destroy', 'bulkDestroy', 'destroyKelompok']),
        ];
    }

    /**
     * Display a listing of Kelompok Interview with their assessment status.
     */
    public function index(Request $request): Response
    {
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('search');
        $selectedJenjangId = $request->input('jenjang_id');
        $cabangId = $request->input('cabang_id');
        $hasExplicitGelombang = $request->has('gelombang_id');
        $gelombangId = $request->input('gelombang_id');
        $gender = $request->input('gender');
        $statusPenilaian = $request->input('status_penilaian');
        $pengujiId = $request->input('penguji_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Master Jenjangs ordered (MTs, MA, S1, S2, S3)
        $jenjangOrder = ['MTS' => 1, 'MA' => 2, 'S1' => 3, 'S2' => 4, 'S3' => 5];
        $allJenjangs = Jenjang::get()->sort(function ($a, $b) use ($jenjangOrder) {
            $orderA = $jenjangOrder[strtoupper($a->code ?? $a->singkatan ?? '')] ?? 99;
            $orderB = $jenjangOrder[strtoupper($b->code ?? $b->singkatan ?? '')] ?? 99;

            return $orderA - $orderB;
        })->values();

        // Get Active Academic Year
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $hasActiveTahunAkademik = $activeTahunAkademik !== null;

        if (! $hasActiveTahunAkademik) {
            $emptyMetrics = [
                'total_kelompok' => 0,
                'total_peserta' => 0,
                'belum_dinilai' => 0,
                'sedang_dinilai' => 0,
                'selesai_dinilai' => 0,
                'nilai_terkunci' => 0,
            ];

            return Inertia::render('Admin/Pendaftar/PenilaianInterview/Index', [
                'kelompokUjians' => KelompokUjian::whereRaw('1 = 0')->paginate($limit)->withQueryString(),
                'metrics' => $emptyMetrics,
                'jenjangs' => $allJenjangs,
                'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
                'cabangs' => Cabang::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'activeTahunAkademik' => null,
                'hasActiveTahunAkademik' => false,
                'gelombangs' => [],
                'pengujis' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
                'koordinator' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
                'pengawas' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
                'kategoriPenilaians' => KategoriPenilaian::where('is_active', true)->get(),
                'filters' => [
                    'limit' => $limit,
                    'search' => (string) ($search ?? ''),
                    'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                    'cabang_id' => (string) ($cabangId ?? ''),
                    'gelombang_id' => (string) ($gelombangId ?? ''),
                    'gender' => (string) ($gender ?? ''),
                    'status_penilaian' => (string) ($statusPenilaian ?? ''),
                    'penguji_id' => (string) ($pengujiId ?? ''),
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
                $gelombangId = '';
            }
        }

        $user = auth()->user();

        // Calculate Overall Assessment Metrics for Active Groups (strictly scoped to active academic year and selected wave)
        $activeKelompokQuery = KelompokUjian::whereIn('status', [
            StatusKelompokUjian::Scheduled,
            StatusKelompokUjian::InProgress,
        ])->whereHas('pendaftars', function ($pq) use ($tahunAkademikId, $gelombangId) {
            $pq->whereHas('periode', fn ($perQ) => $perQ->where('tahun_akademik_id', $tahunAkademikId))
                ->when($gelombangId, fn ($wQ) => $wQ->where('gelombang_id', $gelombangId));
        });

        $activeKelompokIds = $activeKelompokQuery->pluck('id');

        $allAssignedPendaftarIds = DB::table('kelompok_ujian_pendaftar')
            ->whereIn('kelompok_ujian_id', $activeKelompokIds)
            ->join('pendaftars', 'kelompok_ujian_pendaftar.pendaftar_id', '=', 'pendaftars.id')
            ->join('periodes', 'pendaftars.periode_id', '=', 'periodes.id')
            ->where('periodes.tahun_akademik_id', $tahunAkademikId)
            ->when($gelombangId, function ($wQ) use ($gelombangId) {
                $wQ->where('pendaftars.gelombang_id', $gelombangId);
            })
            ->pluck('pendaftars.id')
            ->unique();

        $totalKelompokCount = $activeKelompokIds->count();
        $totalPesertaCount = $allAssignedPendaftarIds->count();

        $hasilUjians = HasilUjian::whereIn('pendaftar_id', $allAssignedPendaftarIds)->get();
        $lockedCount = $hasilUjians->whereNotNull('locked_at')->count();
        $selesaiCount = $hasilUjians->whereNull('locked_at')->filter(function ($h) {
            return $h->total_nilai > 0 && ! empty($h->hasil_wawancara);
        })->count();
        $sedangCount = $hasilUjians->whereNull('locked_at')->filter(function ($h) {
            return ($h->total_nilai > 0 && empty($h->hasil_wawancara)) || ($h->total_nilai == 0 && ! empty($h->hasil_wawancara));
        })->count();
        $belumCount = max(0, $totalPesertaCount - ($lockedCount + $selesaiCount + $sedangCount));

        $metrics = [
            'total_kelompok' => $totalKelompokCount,
            'total_peserta' => $totalPesertaCount,
            'belum_dinilai' => $belumCount,
            'sedang_dinilai' => $sedangCount,
            'selesai_dinilai' => $selesaiCount,
            'nilai_terkunci' => $lockedCount,
        ];

        // Main Query: KelompokUjian (Scheduled & InProgress only, scoped to active academic year)
        $query = KelompokUjian::query()
            ->whereIn('status', [
                StatusKelompokUjian::Scheduled,
                StatusKelompokUjian::InProgress,
            ])
            ->whereHas('pendaftars', function ($pq) use ($tahunAkademikId, $gelombangId) {
                $pq->whereHas('periode', fn ($perQ) => $perQ->where('tahun_akademik_id', $tahunAkademikId))
                    ->when($gelombangId, fn ($wQ) => $wQ->where('gelombang_id', $gelombangId));
            });

        $query->with([
            'pengujis:id,name,email',
            'koordinator:id,name,email',
            'pendaftars' => function ($pq) use ($tahunAkademikId, $gelombangId) {
                $pq->whereHas('periode', fn ($perQ) => $perQ->where('tahun_akademik_id', $tahunAkademikId))
                    ->when($gelombangId, fn ($wQ) => $wQ->where('gelombang_id', $gelombangId))
                    ->with([
                        'cabang:id,name',
                        'jenjang:id,code,name,singkatan,logo_path',
                        'periode:id,name,tahun_akademik_id',
                        'periode.tahunAkademik:id,name',
                        'gelombang:id,name',
                        'hasilUjian',
                        'dokumens.dokumen',
                        'penilaians.aspek:id,kategori_id,nama_aspek,bobot',
                        'penilaians.aspek.kategori:id,nama_kategori',
                        'penilaians.penguji:id,name',
                    ]);
            },
        ])
            ->withCount(['pendaftars', 'penilaians']);

        // Filter: Jenjang
        if ($selectedJenjangId) {
            $query->whereHas('pendaftars', function ($pq) use ($selectedJenjangId) {
                $pq->where('jenjang_id', $selectedJenjangId);
            });
        }

        // Filter: Cabang
        if ($cabangId) {
            $query->whereHas('pendaftars', function ($pq) use ($cabangId) {
                $pq->where('cabang_id', $cabangId);
            });
        }

        // Filter: Gender
        if ($gender) {
            $query->whereHas('pendaftars', function ($pq) use ($gender) {
                $g = strtolower($gender);
                if (str_contains($g, 'laki') || $g === 'l') {
                    $pq->where('personal_data->jenis_kelamin', 'L')
                        ->orWhere('personal_data->jenis_kelamin', 'Laki-Laki')
                        ->orWhere('personal_data->jenis_kelamin', 'Laki-laki');
                } elseif (str_contains($g, 'perempuan') || $g === 'p') {
                    $pq->where('personal_data->jenis_kelamin', 'P')
                        ->orWhere('personal_data->jenis_kelamin', 'Perempuan');
                }
            });
        }

        // Filter: Penguji
        if ($pengujiId) {
            $query->whereHas('pengujis', function ($pq) use ($pengujiId) {
                $pq->where('users.id', $pengujiId);
            });
        }

        // Filter: Rentang Tanggal Ujian
        if ($startDate) {
            $query->whereDate('tanggal_ujian', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_ujian', '<=', $endDate);
        }

        // Filter: Status Penilaian
        if ($statusPenilaian) {
            if ($statusPenilaian === 'belum_dinilai') {
                $query->whereDoesntHave('penilaians')
                    ->whereDoesntHave('pendaftars.hasilUjian', function ($hq) {
                        $hq->where('total_nilai', '>', 0)->orWhereNotNull('hasil_wawancara');
                    });
            } elseif ($statusPenilaian === 'terkunci') {
                $query->whereHas('pendaftars')
                    ->whereDoesntHave('pendaftars', function ($pq) {
                        $pq->whereDoesntHave('hasilUjian', function ($hq) {
                            $hq->whereNotNull('locked_at');
                        });
                    });
            } elseif ($statusPenilaian === 'selesai_dinilai') {
                $query->whereHas('pendaftars')
                    ->whereDoesntHave('pendaftars', function ($pq) {
                        $pq->whereDoesntHave('hasilUjian', function ($hq) {
                            $hq->where(function ($w) {
                                $w->where('total_nilai', '>', 0)->whereNotNull('hasil_wawancara');
                            });
                        });
                    });
            } elseif ($statusPenilaian === 'sebagian_dinilai' || $statusPenilaian === 'sedang_dinilai') {
                $query->where(function ($sq) {
                    $sq->whereHas('penilaians')
                        ->orWhereHas('pendaftars.hasilUjian', function ($hq) {
                            $hq->where('total_nilai', '>', 0)->orWhereNotNull('hasil_wawancara');
                        });
                });
            }
        }

        // Filter: Search Keyword
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelompok', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhereHas('pendaftars', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }

        $kelompokUjians = $query->orderBy('tanggal_ujian', 'desc')->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        // Transform pagination items to attach summary indicators
        $kelompokUjians->getCollection()->transform(function ($kelompok) {
            $totalPeserta = $kelompok->pendaftars->count();
            $dinilaiCount = 0;
            $lockedCount = 0;
            $wawancaraCount = 0;
            $kelasCount = 0;
            $bacaCount = 0;
            $menulisCount = 0;
            $hafalanCount = 0;
            $jenjangsMap = [];

            foreach ($kelompok->pendaftars as $p) {
                if ($p->jenjang) {
                    $jenjangsMap[$p->jenjang->id] = [
                        'id' => $p->jenjang->id,
                        'code' => $p->jenjang->code ?? $p->jenjang->singkatan,
                        'name' => $p->jenjang->name,
                        'logo_path' => $p->jenjang->logo_path,
                    ];
                }

                $hasWawancara = ! empty($p->hasilUjian?->hasil_wawancara);
                $hasKelas = ! empty($p->hasilUjian?->rekomendasi_kelas_pondok);

                $hasBaca = ((float) ($p->hasilUjian?->nilai_baca_kitab ?? 0) > 0)
                    || ! empty($p->hasilUjian?->predikat_baca_kitab)
                    || $p->penilaians->contains(function ($pen) {
                        $cat = strtolower($pen->aspek?->kategori?->nama_kategori ?? '');

                        return str_contains($cat, 'baca') || str_contains($cat, 'membaca');
                    });

                $hasMenulis = ((float) ($p->hasilUjian?->nilai_menulis ?? 0) > 0)
                    || ! empty($p->hasilUjian?->predikat_menulis)
                    || $p->penilaians->contains(function ($pen) {
                        $cat = strtolower($pen->aspek?->kategori?->nama_kategori ?? '');

                        return str_contains($cat, 'tulis') || str_contains($cat, 'menulis');
                    });

                $hasHafalan = ((float) ($p->hasilUjian?->nilai_hafalan ?? 0) > 0)
                    || ! empty($p->hasilUjian?->predikat_hafalan)
                    || $p->penilaians->contains(function ($pen) {
                        $cat = strtolower($pen->aspek?->kategori?->nama_kategori ?? '');

                        return str_contains($cat, 'hafal') || str_contains($cat, 'hafalan');
                    });

                if ($hasWawancara) {
                    $wawancaraCount++;
                }
                if ($hasKelas) {
                    $kelasCount++;
                }
                if ($hasBaca) {
                    $bacaCount++;
                }
                if ($hasMenulis) {
                    $menulisCount++;
                }
                if ($hasHafalan) {
                    $hafalanCount++;
                }

                $isPendaftarLocked = ! empty($p->hasilUjian?->locked_at);
                $isPendaftarScored = ((float) ($p->hasilUjian?->total_nilai ?? 0) > 0) || $hasWawancara;

                if ($isPendaftarLocked) {
                    $lockedCount++;
                }
                if ($isPendaftarScored) {
                    $dinilaiCount++;
                }
            }

            $kelompok->total_peserta = $totalPeserta;
            $kelompok->peserta_count = $totalPeserta;
            $kelompok->total_dinilai = $dinilaiCount;
            $kelompok->dinilai_count = $dinilaiCount;
            $kelompok->total_locked = $lockedCount;
            $kelompok->locked_count = $lockedCount;
            $kelompok->total_wawancara = $wawancaraCount;
            $kelompok->wawancara_count = $wawancaraCount;
            $kelompok->total_kelas = $kelasCount;
            $kelompok->kelas_count = $kelasCount;
            $kelompok->total_baca = $bacaCount;
            $kelompok->baca_count = $bacaCount;
            $kelompok->total_menulis = $menulisCount;
            $kelompok->menulis_count = $menulisCount;
            $kelompok->total_hafalan = $hafalanCount;
            $kelompok->hafalan_count = $hafalanCount;
            $kelompok->jenjangs_list = array_values($jenjangsMap);

            if ($totalPeserta > 0 && $lockedCount === $totalPeserta) {
                $kelompok->status_kelompok = 'Terkunci';
            } elseif ($totalPeserta > 0 && $dinilaiCount === $totalPeserta) {
                $kelompok->status_kelompok = 'Selesai Dinilai';
            } elseif ($dinilaiCount > 0) {
                $kelompok->status_kelompok = 'Sebagian Dinilai';
            } else {
                $kelompok->status_kelompok = 'Belum Dinilai';
            }

            $isHMinusOneOrMore = $kelompok->tanggal_ujian && Carbon::parse($kelompok->tanggal_ujian)->startOfDay()->greaterThanOrEqualTo(now()->addDay()->startOfDay());
            $hasNoPenilaian = ((int) ($kelompok->penilaians_count ?? 0)) === 0 && $dinilaiCount === 0 && $lockedCount === 0;
            $canModify = $isHMinusOneOrMore && $hasNoPenilaian && $kelompok->status === StatusKelompokUjian::Scheduled;
            $kelompok->can_edit = $canModify;
            $kelompok->can_delete = $canModify;

            return $kelompok;
        });

        // Lookup data
        $cabangs = Cabang::accessibleBy()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $pengujis = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        $koordinator = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);

        $kategoriPenilaians = KategoriPenilaian::with([
            'aspek_penilaians' => function ($aq) {
                $aq->orderBy('urutan', 'asc')->orderBy('created_at', 'asc');
            },
        ])
            ->where('is_active', true)
            ->get();

        return Inertia::render('Admin/Pendaftar/PenilaianInterview/Index', [
            'kelompokUjians' => $kelompokUjians,
            'metrics' => $metrics,
            'jenjangs' => $allJenjangs,
            'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
            'cabangs' => $cabangs,
            'activeTahunAkademik' => [
                'id' => $activeTahunAkademik->id,
                'name' => $activeTahunAkademik->name,
                'is_active' => (bool) $activeTahunAkademik->is_active,
            ],
            'hasActiveTahunAkademik' => true,
            'gelombangs' => $gelombangsData,
            'pengujis' => $pengujis,
            'koordinator' => $koordinator,
            'pengawas' => $koordinator,
            'kategoriPenilaians' => $kategoriPenilaians,
            'filters' => [
                'limit' => $limit,
                'search' => (string) ($search ?? ''),
                'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                'cabang_id' => (string) ($cabangId ?? ''),
                'gelombang_id' => (string) ($gelombangId ?? ''),
                'gender' => (string) ($gender ?? ''),
                'status_penilaian' => (string) ($statusPenilaian ?? ''),
                'penguji_id' => (string) ($pengujiId ?? ''),
                'start_date' => (string) ($startDate ?? ''),
                'end_date' => (string) ($endDate ?? ''),
            ],
        ]);
    }

    /**
     * Lock all scores and finalize results in a KelompokUjian.
     */
    public function lockKelompok(KelompokUjian $kelompokUjian): RedirectResponse
    {
        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk mengunci hasil ujian kelompok ini.');
        }

        $kelompokUjian->load(['pendaftars.hasilUjian']);

        if ($kelompokUjian->pendaftars->isEmpty()) {
            return back()->with('error', 'Kelompok ujian ini tidak memiliki calon santri untuk dikunci.');
        }

        // Validate that all candidates have complete 4 evaluations and a determined decision
        foreach ($kelompokUjian->pendaftars as $pendaftar) {
            $hasil = $pendaftar->hasilUjian;
            $hasWawancara = ! empty($hasil?->hasil_wawancara);
            $hasBaca = ((float) ($hasil?->nilai_baca_kitab ?? 0) > 0) || ! empty($hasil?->predikat_baca_kitab);
            $hasTulis = ((float) ($hasil?->nilai_menulis ?? 0) > 0) || ! empty($hasil?->predikat_menulis);
            $hasHafalan = ((float) ($hasil?->nilai_hafalan ?? 0) > 0) || ! empty($hasil?->predikat_hafalan);
            $statusStr = $hasil?->status_kelulusan instanceof StatusKelulusan ? $hasil->status_kelulusan->value : (string) ($hasil?->status_kelulusan ?? '');
            $hasDecision = in_array($statusStr, ['lulus', 'tidak_lulus'], true);

            if (! $hasWawancara || ! $hasBaca || ! $hasTulis || ! $hasHafalan || ! $hasDecision) {
                return back()->with('error', 'Semua calon santri harus telah dinilai lengkap (Hasil Wawancara, Tes Membaca, Tes Menulis, Tes Hafalan) dan dinyatakan Lulus atau Tidak Lulus sebelum hasil dapat dikunci.');
            }
        }

        DB::transaction(function () use ($kelompokUjian) {
            $userId = auth()->id();
            $now = now();

            foreach ($kelompokUjian->pendaftars as $pendaftar) {
                $hasil = $pendaftar->hasilUjian;
                $statusStr = $hasil?->status_kelulusan instanceof StatusKelulusan ? $hasil->status_kelulusan->value : (string) ($hasil?->status_kelulusan ?? '');

                if ($hasil) {
                    $hasil->update([
                        'locked_at' => $now,
                        'locked_by' => $userId,
                    ]);
                } else {
                    $hasil = HasilUjian::create([
                        'pendaftar_id' => $pendaftar->id,
                        'kelompok_ujian_id' => $kelompokUjian->id,
                        'total_nilai' => 0,
                        'locked_at' => $now,
                        'locked_by' => $userId,
                        'status_kelulusan' => null,
                    ]);
                }

                // Update Pendaftar status to LULUS or TIDAK_LULUS
                if ($statusStr === 'lulus') {
                    $pendaftar->status = PendaftarStatus::Lulus;
                } elseif ($statusStr === 'tidak_lulus') {
                    $pendaftar->status = PendaftarStatus::TidakLulus;
                }
                $pendaftar->save();
            }

            // Update KelompokUjian status to Completed
            $kelompokUjian->status = StatusKelompokUjian::Completed;
            $kelompokUjian->save();
        });

        return back()->with('success', 'Semua hasil ujian pada kelompok '.$kelompokUjian->nama_kelompok.' berhasil dikunci dan status kelulusan telah diperbarui.');
    }

    /**
     * Unlock all scores in a KelompokUjian.
     */
    public function unlockKelompok(KelompokUjian $kelompokUjian): RedirectResponse
    {
        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk membuka kunci hasil ujian kelompok ini.');
        }

        DB::transaction(function () use ($kelompokUjian) {
            $kelompokUjian->load('pendaftars.hasilUjian');

            foreach ($kelompokUjian->pendaftars as $pendaftar) {
                if ($pendaftar->hasilUjian) {
                    $pendaftar->hasilUjian->update([
                        'locked_at' => null,
                        'locked_by' => null,
                    ]);
                }

                // Revert pendaftar status back to INTERVIEW if was LULUS/TIDAK_LULUS
                if (in_array($pendaftar->status, [PendaftarStatus::Lulus, PendaftarStatus::TidakLulus], true)) {
                    $pendaftar->status = PendaftarStatus::Interview;
                    $pendaftar->save();
                }
            }

            $kelompokUjian->status = StatusKelompokUjian::InProgress;
            $kelompokUjian->save();
        });

        return back()->with('success', 'Kunci hasil ujian pada kelompok '.$kelompokUjian->nama_kelompok.' berhasil dibuka.');
    }

    /**
     * Store academic scores for a candidate across categories and aspects.
     */
    public function storeScore(Request $request): RedirectResponse
    {
        $request->validate([
            'pendaftar_id' => 'required|string|exists:pendaftars,id',
            'scores' => 'required|array',
            'scores.*' => 'nullable|numeric|min:0|max:100',
            'catatans' => 'nullable|array',
            'kelompok_ujian_id' => 'nullable|string|exists:kelompok_ujians,id',
        ]);

        $pendaftar = Pendaftar::with(['kelompokUjians', 'hasilUjian'])->findOrFail($request->input('pendaftar_id'));
        $kelompokId = $request->input('kelompok_ujian_id') ?? $pendaftar->kelompokUjians->first()?->id;
        $kelompokUjian = KelompokUjian::findOrFail($kelompokId);

        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $isAssignedExaminer) {
            return back()->with('error', 'Hanya penguji atau koordinator yang ditugaskan pada kelompok ujian ini yang dapat melakukan penilaian.');
        }

        // Check if locked
        if ($pendaftar->hasilUjian && $pendaftar->hasilUjian->locked_at) {
            return back()->with('error', 'Nilai calon santri ini telah dikunci (final) dan tidak dapat diubah.');
        }

        $kelompokId = $request->input('kelompok_ujian_id') ?? $pendaftar->kelompokUjians->first()?->id;
        $pengujiId = auth()->id();

        DB::transaction(function () use ($request, $pendaftar, $kelompokId, $pengujiId) {
            $scores = $request->input('scores', []);
            $catatans = $request->input('catatans', []);

            foreach ($scores as $aspekId => $nilai) {
                if ($nilai === null || $nilai === '') {
                    continue;
                }

                $aspek = AspekPenilaian::find($aspekId);
                if (! $aspek) {
                    continue;
                }

                Penilaian::updateOrCreate(
                    [
                        'pendaftar_id' => $pendaftar->id,
                        'aspek_id' => $aspekId,
                        'kelompok_ujian_id' => $kelompokId,
                    ],
                    [
                        'penguji_id' => $pengujiId,
                        'nilai' => (float) $nilai,
                        'catatan' => $catatans[$aspekId] ?? null,
                    ]
                );
            }

            // Recalculate category totals and predicates
            $this->recalculateHasilUjian($pendaftar->id, $kelompokId);
        });

        return back()->with('success', 'Nilai ujian santri berhasil disimpan.');
    }

    /**
     * Store comprehensive 7-section interview form.
     */
    public function showWawancaraPage(Request $request, $kelompokUjianId, $pendaftarId)
    {
        $user = auth()->user();
        $kelompokUjian = KelompokUjian::findOrFail($kelompokUjianId);
        $pendaftar = Pendaftar::with([
            'jenjang',
            'cabang',
            'gelombang',
            'periode',
            'dokumens.dokumen',
            'hasilUjian' => function ($q) use ($kelompokUjianId) {
                $q->where('kelompok_ujian_id', $kelompokUjianId)->with('dataWawancara');
            },
        ])->findOrFail($pendaftarId);

        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data wawancara calon santri ini.');
        }

        $kopSuratPath = Setting::where('key', 'kop_surat_path')->value('value');
        $kopSuratUrl = $kopSuratPath ? Storage::url($kopSuratPath) : '/image/kop-surat.png';

        $wawancaraData = [];
        $hasilWawancara = null;
        $rekomendasiKelasPondok = null;
        $catatanFinal = null;

        if ($pendaftar->hasilUjian) {
            $hasilWawancara = $pendaftar->hasilUjian->hasil_wawancara;
            $rekomendasiKelasPondok = $pendaftar->hasilUjian->rekomendasi_kelas_pondok;
            $catatanFinal = $pendaftar->hasilUjian->catatan_final;

            if ($pendaftar->hasilUjian->dataWawancara) {
                $hw = $pendaftar->hasilUjian->dataWawancara;
                $wawancaraData = [
                    'motivasi' => [
                        'cita_cita' => $hw->motivasi_cita_cita ?? '',
                        'keinginan_mondok' => $hw->motivasi_keinginan_mondok ?? '',
                        'bersedia_mondok_4_tahun' => $hw->motivasi_bersedia_4_tahun ?? '',
                        'tidak_ambil_ijazah' => $hw->motivasi_tidak_ambil_ijazah ?? '',
                        'catatan_tidak_ambil_ijazah' => $hw->motivasi_catatan ?? '',
                        'kenalan' => [
                            'nama' => $hw->motivasi_kenalan_nama ?? '',
                            'status' => $hw->motivasi_kenalan_hubungan ?? '',
                        ],
                    ],
                    'kebiasaan' => [
                        'jam_tidur' => $hw->kebiasaan_jam_tidur ?? '',
                        'jam_bangun' => $hw->kebiasaan_jam_bangun ?? '',
                        'kegiatan_malam' => $hw->kebiasaan_kegiatan_malam ?? '',
                        'riwayat_penyakit' => $hw->kebiasaan_riwayat_penyakit ?? '',
                    ],
                    'ibadah' => [
                        'sholat_5_waktu' => $hw->ibadah_sholat_5_waktu ?? '',
                        'sholat_berjamaah' => $hw->ibadah_sholat_berjamaah ?? '',
                        'shodaqoh' => $hw->ibadah_shodaqoh ?? '',
                        'membantu' => $hw->ibadah_membantu_orang ?? '',
                        'catatan' => $hw->ibadah_catatan ?? '',
                        'bacaan_sholat' => $hw->ibadah_bacaan_sholat ?? [],
                        'catatan_bacaan' => $hw->ibadah_bacaan_catatan ?? '',
                    ],
                    'pelanggaran' => [
                        'pernah_dilakukan' => $hw->pelanggaran_pernah_dilakukan ?? [],
                        'catatan' => $hw->pelanggaran_catatan ?? '',
                    ],
                    'prestasi' => [
                        'items' => $hw->prestasi_items ?? [],
                        'catatan_pondok' => $hw->prestasi_catatan_pondok ?? '',
                        'catatan_sekolah' => $hw->prestasi_catatan_sekolah ?? '',
                    ],
                ];
            }
        }

        $dbStep = (int) ($pendaftar->hasilUjian?->dataWawancara?->current_step ?? 1);
        $activeStep = (int) $request->query('step', $dbStep);

        return Inertia::render('Admin/Pendaftar/PenilaianInterview/WawancaraPage', [
            'kelompok' => $kelompokUjian,
            'pendaftar' => $pendaftar,
            'kopSuratUrl' => $kopSuratUrl,
            'wawancaraData' => $wawancaraData,
            'hasilWawancara' => $hasilWawancara,
            'rekomendasiKelasPondok' => $rekomendasiKelasPondok,
            'catatanFinal' => $catatanFinal,
            'activeStep' => $activeStep,
            'currentStep' => $dbStep,
            'isAssignedExaminer' => $isAssignedExaminer,
            'backUrl' => route('admin.pendaftar.penilaian_interview.show_kelompok', $kelompokUjian->id),
        ]);
    }

    public function storeInterviewNote(Request $request): RedirectResponse
    {
        $request->validate([
            'pendaftar_id' => 'required|string|exists:pendaftars,id',
            'kelompok_ujian_id' => 'required|string|exists:kelompok_ujians,id',
            'current_step' => 'nullable|integer|min:1|max:6',
            'next_step' => 'nullable|integer|min:1|max:6',
            'is_finished' => 'nullable|boolean',
            'wawancara_data' => 'nullable|array',
            'hasil_wawancara' => 'nullable|string|in:A,C,D',
            'rekomendasi_kelas_pondok' => 'nullable|string|max:100',
            'catatan_final' => 'nullable|string',
        ]);

        $user = auth()->user();
        $kelompokUjian = KelompokUjian::findOrFail($request->input('kelompok_ujian_id'));
        $pendaftar = Pendaftar::findOrFail($request->input('pendaftar_id'));

        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $isAssignedExaminer) {
            return back()->with('error', 'Hanya penguji atau koordinator yang ditugaskan pada kelompok ujian ini yang dapat mengisi/menyimpan formulir wawancara.');
        }

        try {
            DB::beginTransaction();

            $hasilUjian = HasilUjian::firstOrCreate(
                [
                    'pendaftar_id' => $request->input('pendaftar_id'),
                    'kelompok_ujian_id' => $request->input('kelompok_ujian_id'),
                ],
                [
                    'status_kelulusan' => null,
                ]
            );

            if ($hasilUjian->locked_at) {
                return back()->with('error', 'Wawancara calon santri ini telah dikunci dan tidak dapat diubah lagi.');
            }

            $wawancara = HasilWawancara::firstOrNew(['hasil_ujian_id' => $hasilUjian->id]);

            // Merge existing data with new step data
            $incomingData = $request->input('wawancara_data');

            if (isset($incomingData['motivasi'])) {
                $wawancara->motivasi_cita_cita = $incomingData['motivasi']['cita_cita'] ?? $wawancara->motivasi_cita_cita;
                $wawancara->motivasi_keinginan_mondok = $incomingData['motivasi']['keinginan_mondok'] ?? $wawancara->motivasi_keinginan_mondok;
                $wawancara->motivasi_bersedia_4_tahun = $incomingData['motivasi']['bersedia_mondok_4_tahun'] ?? $wawancara->motivasi_bersedia_4_tahun;
                $wawancara->motivasi_tidak_ambil_ijazah = $incomingData['motivasi']['tidak_ambil_ijazah'] ?? $wawancara->motivasi_tidak_ambil_ijazah;
                $wawancara->motivasi_catatan = $incomingData['motivasi']['catatan_tidak_ambil_ijazah'] ?? $wawancara->motivasi_catatan;
                $wawancara->motivasi_kenalan_nama = $incomingData['motivasi']['kenalan']['nama'] ?? $wawancara->motivasi_kenalan_nama;
                $wawancara->motivasi_kenalan_hubungan = $incomingData['motivasi']['kenalan']['status'] ?? $wawancara->motivasi_kenalan_hubungan;
            }
            if (isset($incomingData['kebiasaan'])) {
                $wawancara->kebiasaan_jam_tidur = $incomingData['kebiasaan']['jam_tidur'] ?? $wawancara->kebiasaan_jam_tidur;
                $wawancara->kebiasaan_jam_bangun = $incomingData['kebiasaan']['jam_bangun'] ?? $wawancara->kebiasaan_jam_bangun;
                $wawancara->kebiasaan_kegiatan_malam = $incomingData['kebiasaan']['kegiatan_malam'] ?? $wawancara->kebiasaan_kegiatan_malam;
                $wawancara->kebiasaan_riwayat_penyakit = $incomingData['kebiasaan']['riwayat_penyakit'] ?? $wawancara->kebiasaan_riwayat_penyakit;
            }
            if (isset($incomingData['ibadah'])) {
                $wawancara->ibadah_sholat_5_waktu = $incomingData['ibadah']['sholat_5_waktu'] ?? $wawancara->ibadah_sholat_5_waktu;
                $wawancara->ibadah_sholat_berjamaah = $incomingData['ibadah']['sholat_berjamaah'] ?? $wawancara->ibadah_sholat_berjamaah;
                $wawancara->ibadah_shodaqoh = $incomingData['ibadah']['shodaqoh'] ?? $wawancara->ibadah_shodaqoh;
                $wawancara->ibadah_membantu_orang = $incomingData['ibadah']['membantu'] ?? $wawancara->ibadah_membantu_orang;
                $wawancara->ibadah_catatan = $incomingData['ibadah']['catatan'] ?? $wawancara->ibadah_catatan;
                $wawancara->ibadah_bacaan_sholat = $incomingData['ibadah']['bacaan_sholat'] ?? $wawancara->ibadah_bacaan_sholat;
                $wawancara->ibadah_bacaan_catatan = $incomingData['ibadah']['catatan_bacaan'] ?? $wawancara->ibadah_bacaan_catatan;
            }
            if (isset($incomingData['pelanggaran'])) {
                $wawancara->pelanggaran_pernah_dilakukan = $incomingData['pelanggaran']['pernah_dilakukan'] ?? $wawancara->pelanggaran_pernah_dilakukan;
                $wawancara->pelanggaran_catatan = $incomingData['pelanggaran']['catatan'] ?? $wawancara->pelanggaran_catatan;
            }
            if (isset($incomingData['prestasi'])) {
                $wawancara->prestasi_items = $incomingData['prestasi']['items'] ?? $wawancara->prestasi_items;
                $wawancara->prestasi_catatan_pondok = $incomingData['prestasi']['catatan_pondok'] ?? $wawancara->prestasi_catatan_pondok;
                $wawancara->prestasi_catatan_sekolah = $incomingData['prestasi']['catatan_sekolah'] ?? $wawancara->prestasi_catatan_sekolah;
            }

            $currentStep = (int) ($request->input('current_step') ?? 1);
            $targetStep = (int) ($request->input('next_step') ?? min(6, $currentStep + 1));
            $wawancara->current_step = max((int) ($wawancara->current_step ?? 1), $targetStep);

            $wawancara->save();

            if ($request->has('hasil_wawancara') && $request->input('hasil_wawancara') !== '') {
                $hasilUjian->hasil_wawancara = $request->input('hasil_wawancara');
            }
            if ($request->has('rekomendasi_kelas_pondok')) {
                $hasilUjian->rekomendasi_kelas_pondok = $request->input('rekomendasi_kelas_pondok') ?: null;
            }
            if ($request->has('catatan_final')) {
                $hasilUjian->catatan_final = $request->input('catatan_final') ?: null;
            }

            $hasilUjian->save();
            $this->recalculateHasilUjian($request->input('pendaftar_id'), $request->input('kelompok_ujian_id'));

            DB::commit();

            $message = 'Data langkah '.$currentStep.' berhasil disimpan.';

            if ($request->boolean('is_finished')) {
                return redirect()->route('admin.pendaftar.penilaian_interview.show_kelompok', $request->input('kelompok_ujian_id'))
                    ->with('success', 'Formulir wawancara calon santri berhasil disimpan.');
            }

            if ($request->filled('next_step')) {
                return redirect()->route('admin.pendaftar.penilaian_interview.wawancara.show', [
                    'kelompokUjian' => $request->input('kelompok_ujian_id'),
                    'pendaftar' => $request->input('pendaftar_id'),
                    'step' => $targetStep,
                ])->with('success', $message);
            }

            return redirect()->route('admin.pendaftar.penilaian_interview.show_kelompok', $request->input('kelompok_ujian_id'))
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storeInterviewNote: '.$e->getMessage());

            return back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    /**
     * Store kelulusan decision (lulus or tidak_lulus) for a candidate.
     */
    public function storeKelulusan(Request $request): RedirectResponse
    {
        $request->validate([
            'pendaftar_id' => 'required|string|exists:pendaftars,id',
            'kelompok_ujian_id' => 'required|string|exists:kelompok_ujians,id',
            'status_kelulusan' => 'nullable|string|in:lulus,tidak_lulus',
        ]);

        $user = auth()->user();
        $kelompokUjian = KelompokUjian::findOrFail($request->input('kelompok_ujian_id'));
        $pendaftar = Pendaftar::findOrFail($request->input('pendaftar_id'));

        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menentukan kelulusan calon santri ini.');
        }

        try {
            DB::beginTransaction();

            $pendaftarId = $request->input('pendaftar_id');
            $kelompokId = $request->input('kelompok_ujian_id');
            $statusKelulusan = $request->input('status_kelulusan');

            $hasilUjian = HasilUjian::firstOrCreate(
                [
                    'pendaftar_id' => $pendaftarId,
                    'kelompok_ujian_id' => $kelompokId,
                ],
                [
                    'status_kelulusan' => null,
                ]
            );

            if ($hasilUjian->locked_at) {
                return back()->with('error', 'Hasil ujian kelompok telah dikunci dan keputusan kelulusan tidak dapat diubah.');
            }

            // Verify that 4 tests are evaluated before decision
            $hasWawancara = ! empty($hasilUjian->hasil_wawancara);
            $hasBaca = ((float) $hasilUjian->nilai_baca_kitab > 0) || ! empty($hasilUjian->predikat_baca_kitab);
            $hasTulis = ((float) $hasilUjian->nilai_menulis > 0) || ! empty($hasilUjian->predikat_menulis);
            $hasHafalan = ((float) $hasilUjian->nilai_hafalan > 0) || ! empty($hasilUjian->predikat_hafalan);

            if (! empty($statusKelulusan) && (! $hasWawancara || ! $hasBaca || ! $hasTulis || ! $hasHafalan)) {
                return back()->with('error', 'Status kelulusan hanya dapat ditentukan setelah Hasil Wawancara, Tes Membaca, Tes Menulis, dan Tes Hafalan selesai dinilai.');
            }

            $hasilUjian->status_kelulusan = match ($statusKelulusan) {
                'lulus' => StatusKelulusan::Lulus,
                'tidak_lulus' => StatusKelulusan::TidakLulus,
                default => null,
            };

            // If tidak lulus, clear rekomendasi kelas pondok
            if ($statusKelulusan === 'tidak_lulus') {
                $hasilUjian->rekomendasi_kelas_pondok = null;
            }

            $hasilUjian->save();

            DB::commit();

            return back()->with('success', 'Keputusan kelulusan calon santri berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storeKelulusan: '.$e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat menyimpan keputusan kelulusan.');
        }
    }

    /**
     * Store penentuan kelas for a candidate.
     */
    public function storePenentuanKelas(Request $request): RedirectResponse
    {
        $request->validate([
            'pendaftar_id' => 'required|string|exists:pendaftars,id',
            'kelompok_ujian_id' => 'required|string|exists:kelompok_ujians,id',
            'rekomendasi_kelas_pondok' => 'required|string|max:100',
            'catatan_final' => 'nullable|string',
        ]);

        $user = auth()->user();
        $kelompokUjian = KelompokUjian::findOrFail($request->input('kelompok_ujian_id'));
        $pendaftar = Pendaftar::findOrFail($request->input('pendaftar_id'));

        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menentukan kelas pondok calon santri ini.');
        }

        try {
            DB::beginTransaction();

            $pendaftarId = $request->input('pendaftar_id');
            $kelompokId = $request->input('kelompok_ujian_id');

            $hasilUjian = HasilUjian::firstOrCreate(
                [
                    'pendaftar_id' => $pendaftarId,
                    'kelompok_ujian_id' => $kelompokId,
                ],
                [
                    'status_kelulusan' => null,
                ]
            );

            // Cannot set class if candidate is marked TIDAK_LULUS
            $statusStr = $hasilUjian->status_kelulusan instanceof StatusKelulusan ? $hasilUjian->status_kelulusan->value : (string) ($hasilUjian->status_kelulusan ?? '');
            if ($statusStr === 'tidak_lulus') {
                return back()->with('error', 'Calon santri dinyatakan tidak lulus, penentuan kelas pondok tidak dapat dilakukan.');
            }

            $hasilUjian->rekomendasi_kelas_pondok = $request->input('rekomendasi_kelas_pondok');
            if ($request->has('catatan_final')) {
                $hasilUjian->catatan_final = $request->input('catatan_final');
            }

            $hasilUjian->save();

            DB::commit();

            return back()->with('success', 'Penentuan kelas pondok berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storePenentuanKelas: '.$e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat menyimpan penentuan kelas.');
        }
    }

    /**
     * Show dedicated Lembar Tes page (Tes Membaca, Tes Menulis, or Tes Hafalan).
     */
    public function showLembarTes(KelompokUjian $kelompokUjian, string $kategoriSlug, Request $request): Response
    {
        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk lembar tes kelompok ini.');
        }

        $kelompokUjian = KelompokUjian::with([
            'pengujis' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email', 'users.nik', 'users.nip', 'users.foto')
                    ->with(['roles:id,name']);
            },
            'koordinator' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email', 'users.nik', 'users.nip', 'users.foto')
                    ->with(['roles:id,name']);
            },
            'pendaftars' => function ($pq) use ($kelompokUjian) {
                $pq->with([
                    'jenjang',
                    'cabang',
                    'hasilUjian' => function ($hq) use ($kelompokUjian) {
                        $hq->where(function ($w) use ($kelompokUjian) {
                            $w->where('kelompok_ujian_id', $kelompokUjian->id)
                                ->orWhereNull('kelompok_ujian_id');
                        });
                    },
                    'penilaians' => function ($nq) use ($kelompokUjian) {
                        $nq->where('kelompok_ujian_id', $kelompokUjian->id)
                            ->with([
                                'aspek:id,kategori_id,nama_aspek,bobot',
                                'penguji:id,name',
                            ]);
                    },
                ]);
            },
        ])
            ->findOrFail($kelompokUjian->id);

        // Find category by slug
        $kategoriQuery = KategoriPenilaian::with([
            'aspek_penilaians' => function ($aq) {
                $aq->orderBy('urutan', 'asc')->orderBy('created_at', 'asc');
            },
        ])->where('is_active', true);

        if ($kategoriSlug === 'tes-membaca') {
            $kategori = $kategoriQuery->where(function ($q) {
                $q->where('nama_kategori', 'like', '%baca%')
                    ->orWhere('nama_kategori', 'like', '%kitab%');
            })->firstOrFail();
            $examiners = $kelompokUjian->pengujis->where('pivot.peran', 'tes_membaca')->values();
        } elseif ($kategoriSlug === 'tes-menulis') {
            $kategori = $kategoriQuery->where(function ($q) {
                $q->where('nama_kategori', 'like', '%tulis%')
                    ->orWhere('nama_kategori', 'like', '%menulis%')
                    ->orWhere('nama_kategori', 'like', '%imla%');
            })->firstOrFail();
            $examiners = $kelompokUjian->pengujis->where('pivot.peran', 'tes_menulis')->values();
        } else {
            // Default to hafalan
            $kategoriSlug = 'tes-hafalan';
            $kategori = $kategoriQuery->where(function ($q) {
                $q->where('nama_kategori', 'like', '%hafal%')
                    ->orWhere('nama_kategori', 'like', '%tahfidz%');
            })->firstOrFail();
            $examiners = $kelompokUjian->pengujis->where('pivot.peran', 'tes_hafalan')->values();
        }

        return Inertia::render('Admin/Pendaftar/PenilaianInterview/LembarTes', [
            'kelompok' => [
                'id' => $kelompokUjian->id,
                'nama_kelompok' => $kelompokUjian->nama_kelompok,
                'tanggal_ujian' => $kelompokUjian->tanggal_ujian ? (is_string($kelompokUjian->tanggal_ujian) ? $kelompokUjian->tanggal_ujian : $kelompokUjian->tanggal_ujian->toDateString()) : '',
                'waktu_mulai' => $kelompokUjian->waktu_mulai ? substr($kelompokUjian->waktu_mulai, 0, 5) : '08:00',
                'waktu_selesai' => $kelompokUjian->waktu_selesai ? substr($kelompokUjian->waktu_selesai, 0, 5) : '12:00',
                'lokasi' => $kelompokUjian->lokasi,
                'status' => $kelompokUjian->status,
            ],
            'kategori' => $kategori,
            'kategoriSlug' => $kategoriSlug,
            'pendaftars' => $kelompokUjian->pendaftars,
            'examiners' => $examiners,
            'isAssignedExaminer' => $isAssignedExaminer,
            'backUrl' => route('admin.pendaftar.penilaian_interview.show_kelompok', $kelompokUjian->id),
        ]);
    }

    /**
     * Save single candidate score from Lembar Tes.
     */
    public function saveSingleScore(KelompokUjian $kelompokUjian, string $kategoriSlug, Request $request): RedirectResponse
    {
        $request->validate([
            'pendaftar_id' => 'required|string|exists:pendaftars,id',
            'scores' => 'required|array',
            'scores.*' => 'nullable|numeric|min:0|max:100',
            'catatan' => 'nullable|string',
        ]);

        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $isAssignedExaminer) {
            return back()->with('error', 'Hanya penguji atau koordinator yang ditugaskan pada kelompok ini yang dapat menyimpan nilai.');
        }

        try {
            DB::beginTransaction();

            $pendaftarId = $request->input('pendaftar_id');
            $kelompokId = $kelompokUjian->id;
            $pengujiId = auth()->id();
            $scores = $request->input('scores', []);
            $catatan = $request->input('catatan');

            $existingHasil = HasilUjian::where('pendaftar_id', $pendaftarId)
                ->where('kelompok_ujian_id', $kelompokId)
                ->first();
            if ($existingHasil?->locked_at) {
                return back()->with('error', 'Penilaian calon santri ini telah dikunci dan tidak dapat diubah lagi.');
            }

            foreach ($scores as $aspekId => $nilai) {
                if ($nilai === null || $nilai === '') {
                    continue;
                }

                $aspek = AspekPenilaian::find($aspekId);
                if (! $aspek) {
                    continue;
                }

                Penilaian::updateOrCreate(
                    [
                        'pendaftar_id' => $pendaftarId,
                        'aspek_id' => $aspekId,
                        'kelompok_ujian_id' => $kelompokId,
                    ],
                    [
                        'penguji_id' => $pengujiId,
                        'nilai' => (float) $nilai,
                        'catatan' => $catatan,
                    ]
                );
            }

            $this->recalculateHasilUjian($pendaftarId, $kelompokId);

            DB::commit();

            return back()->with('success', 'Nilai santri berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saveSingleScore: '.$e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat menyimpan nilai.');
        }
    }

    /**
     * Save batch scores from Lembar Tes.
     */
    public function saveBatchScore(KelompokUjian $kelompokUjian, string $kategoriSlug, Request $request): RedirectResponse
    {
        $request->validate([
            'rows' => 'required|array',
            'rows.*.pendaftar_id' => 'required|string|exists:pendaftars,id',
            'rows.*.scores' => 'required|array',
            'rows.*.catatan' => 'nullable|string',
        ]);

        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $isAssignedExaminer) {
            return back()->with('error', 'Hanya penguji atau koordinator yang ditugaskan pada kelompok ini yang dapat menyimpan nilai.');
        }

        try {
            DB::beginTransaction();

            $kelompokId = $kelompokUjian->id;
            $pengujiId = auth()->id();
            $affectedPendaftarIds = [];

            foreach ($request->input('rows', []) as $row) {
                $pendaftarId = $row['pendaftar_id'] ?? null;
                if (! $pendaftarId) {
                    continue;
                }

                $existingHasil = HasilUjian::where('pendaftar_id', $pendaftarId)
                    ->where('kelompok_ujian_id', $kelompokId)
                    ->first();
                if ($existingHasil?->locked_at) {
                    continue;
                }

                $scores = $row['scores'] ?? [];
                $catatan = $row['catatan'] ?? null;
                $hasScore = false;

                foreach ($scores as $aspekId => $nilai) {
                    if ($nilai === null || $nilai === '') {
                        continue;
                    }

                    $aspek = AspekPenilaian::find($aspekId);
                    if (! $aspek) {
                        continue;
                    }

                    Penilaian::updateOrCreate(
                        [
                            'pendaftar_id' => $pendaftarId,
                            'aspek_id' => $aspekId,
                            'kelompok_ujian_id' => $kelompokId,
                        ],
                        [
                            'penguji_id' => $pengujiId,
                            'nilai' => (float) $nilai,
                            'catatan' => $catatan,
                        ]
                    );
                    $hasScore = true;
                }

                if ($hasScore) {
                    $affectedPendaftarIds[] = $pendaftarId;
                }
            }

            foreach (array_unique($affectedPendaftarIds) as $pid) {
                $this->recalculateHasilUjian($pid, $kelompokId);
            }

            DB::commit();

            return back()->with('success', 'Seluruh nilai lembar tes berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saveBatchScore: '.$e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat menyimpan nilai.');
        }
    }

    /**
     * Lock/finalize scores for an individual candidate.
     */
    public function finalize(Request $request, string $pendaftarId): RedirectResponse
    {
        $request->validate([
            'kelompok_ujian_id' => 'required|string|exists:kelompok_ujians,id',
        ]);

        $kelompokId = $request->input('kelompok_ujian_id');
        $pendaftar = Pendaftar::with(['hasilUjian' => function ($q) use ($kelompokId) {
            $q->where('kelompok_ujian_id', $kelompokId);
        }])->findOrFail($pendaftarId);

        $user = auth()->user();
        $isAssignedExaminer = $pendaftar->kelompokUjians()->where(function ($kq) use ($user) {
            $kq->whereHas('pengujis', fn ($uq) => $uq->where('users.id', $user->id))
                ->orWhereHas('koordinator', fn ($uq) => $uq->where('users.id', $user->id));
        })->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk mengunci nilai calon santri ini.');
        }

        $hasil = $pendaftar->hasilUjian;

        if (! $hasil) {
            return back()->with('error', 'Data penilaian calon santri belum ditemukan.');
        }

        // Validate that all 4 evaluations are complete and decision is made
        $hasWawancara = ! empty($hasil->hasil_wawancara);
        $hasBaca = ((float) ($hasil->nilai_baca_kitab ?? 0) > 0) || ! empty($hasil->predikat_baca_kitab);
        $hasTulis = ((float) ($hasil->nilai_menulis ?? 0) > 0) || ! empty($hasil->predikat_menulis);
        $hasHafalan = ((float) ($hasil->nilai_hafalan ?? 0) > 0) || ! empty($hasil->predikat_hafalan);
        $statusStr = $hasil->status_kelulusan instanceof StatusKelulusan ? $hasil->status_kelulusan->value : (string) ($hasil->status_kelulusan ?? '');
        $hasDecision = in_array($statusStr, ['lulus', 'tidak_lulus'], true);

        if (! $hasWawancara || ! $hasBaca || ! $hasTulis || ! $hasHafalan || ! $hasDecision) {
            return back()->with('error', 'Calon santri harus telah dinilai lengkap (Hasil Wawancara, Tes Membaca, Tes Menulis, Tes Hafalan) dan dinyatakan Lulus atau Tidak Lulus sebelum nilai dapat dikunci.');
        }

        DB::transaction(function () use ($pendaftar, $hasil, $statusStr, $kelompokId) {
            $now = now();
            $authId = auth()->id();

            $hasil->update([
                'locked_at' => $now,
                'locked_by' => $authId,
            ]);

            if (! $hasil->nomor_surat_hasil) {
                $monthRoman = $this->getRomanMonth((int) date('n'));
                $year = date('Y');
                $count = HasilUjian::whereNotNull('nomor_surat_hasil')->count() + 1;
                $seq = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
                $hasil->nomor_surat_hasil = "{$seq}/PPB-KALBAR/{$monthRoman}/{$year}";
                $hasil->save();
            }

            // Update Pendaftar status to LULUS or TIDAK_LULUS
            if ($statusStr === 'lulus') {
                $pendaftar->status = PendaftarStatus::Lulus;
            } elseif ($statusStr === 'tidak_lulus') {
                $pendaftar->status = PendaftarStatus::TidakLulus;
            }
            $pendaftar->save();

            // Check if ALL candidates in this Kelompok are now locked
            $kelompok = KelompokUjian::with('pendaftars.hasilUjian')->find($kelompokId);
            if ($kelompok && $kelompok->pendaftars->isNotEmpty()) {
                $allLocked = $kelompok->pendaftars->every(function ($p) {
                    return ! empty($p->hasilUjian?->locked_at);
                });
                if ($allLocked) {
                    $kelompok->status = StatusKelompokUjian::Completed;
                    $kelompok->save();
                } elseif ($kelompok->status === StatusKelompokUjian::Scheduled) {
                    $kelompok->status = StatusKelompokUjian::InProgress;
                    $kelompok->save();
                }
            }
        });

        return back()->with('success', "Nilai untuk calon santri {$pendaftar->nama} berhasil dikunci dan status pendaftar telah diperbarui.");
    }

    /**
     * Bulk finalize scores for multiple candidates.
     */
    public function bulkFinalize(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|exists:pendaftars,id',
            'kelompok_ujian_id' => 'required|string|exists:kelompok_ujians,id',
        ]);

        $kelompokId = $request->input('kelompok_ujian_id');
        $kelompok = KelompokUjian::findOrFail($kelompokId);

        $user = auth()->user();
        $isAssignedExaminer = $kelompok->pengujis()->where('users.id', $user->id)->exists()
            || $kelompok->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk mengunci kelompok ini.');
        }

        $ids = $request->input('ids', []);
        $now = now();
        $authId = auth()->id();

        DB::transaction(function () use ($ids, $kelompokId, $now, $authId) {
            foreach ($ids as $pId) {
                $pendaftar = Pendaftar::with(['hasilUjian' => function ($q) use ($kelompokId) {
                    $q->where('kelompok_ujian_id', $kelompokId);
                }])->find($pId);

                if (! $pendaftar || ! $pendaftar->hasilUjian) {
                    continue;
                }

                $hasil = $pendaftar->hasilUjian;
                $hasWawancara = ! empty($hasil->hasil_wawancara);
                $hasBaca = ((float) ($hasil->nilai_baca_kitab ?? 0) > 0) || ! empty($hasil->predikat_baca_kitab);
                $hasTulis = ((float) ($hasil->nilai_menulis ?? 0) > 0) || ! empty($hasil->predikat_menulis);
                $hasHafalan = ((float) ($hasil->nilai_hafalan ?? 0) > 0) || ! empty($hasil->predikat_hafalan);
                $statusStr = $hasil->status_kelulusan instanceof StatusKelulusan ? $hasil->status_kelulusan->value : (string) ($hasil->status_kelulusan ?? '');
                $hasDecision = in_array($statusStr, ['lulus', 'tidak_lulus'], true);

                if (! $hasWawancara || ! $hasBaca || ! $hasTulis || ! $hasHafalan || ! $hasDecision) {
                    continue;
                }

                $hasil->locked_at = $now;
                $hasil->locked_by = $authId;

                if (! $hasil->nomor_surat_hasil) {
                    $monthRoman = $this->getRomanMonth((int) date('n'));
                    $year = date('Y');
                    $count = HasilUjian::whereNotNull('nomor_surat_hasil')->count() + 1;
                    $seq = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
                    $hasil->nomor_surat_hasil = "{$seq}/PPB-KALBAR/{$monthRoman}/{$year}";
                }

                $hasil->save();

                if ($statusStr === 'lulus') {
                    $pendaftar->status = PendaftarStatus::Lulus;
                } elseif ($statusStr === 'tidak_lulus') {
                    $pendaftar->status = PendaftarStatus::TidakLulus;
                }
                $pendaftar->save();
            }

            // Check if ALL candidates in this Kelompok are now locked
            $kelompok = KelompokUjian::with('pendaftars.hasilUjian')->find($kelompokId);
            if ($kelompok && $kelompok->pendaftars->isNotEmpty()) {
                $allLocked = $kelompok->pendaftars->every(function ($p) {
                    return ! empty($p->hasilUjian?->locked_at);
                });
                if ($allLocked) {
                    $kelompok->status = StatusKelompokUjian::Completed;
                    $kelompok->save();
                }
            }
        });

        return back()->with('success', count($ids).' data penilaian calon santri berhasil dikunci kolektif.');
    }

    /**
     * Unlock scores for an individual candidate (Admin action).
     */
    public function unlock(Request $request, string $pendaftarId): RedirectResponse
    {
        $kelompokId = $request->input('kelompok_ujian_id');
        $pendaftar = Pendaftar::with(['hasilUjian' => function ($q) use ($kelompokId) {
            if ($kelompokId) {
                $q->where('kelompok_ujian_id', $kelompokId);
            }
        }])->findOrFail($pendaftarId);

        $user = auth()->user();
        $isAssignedExaminer = $pendaftar->kelompokUjians()->where(function ($kq) use ($user) {
            $kq->whereHas('pengujis', fn ($uq) => $uq->where('users.id', $user->id))
                ->orWhereHas('koordinator', fn ($uq) => $uq->where('users.id', $user->id));
        })->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk membuka kunci nilai calon santri ini.');
        }

        DB::transaction(function () use ($pendaftar, $kelompokId) {
            if ($pendaftar->hasilUjian) {
                $pendaftar->hasilUjian->update([
                    'locked_at' => null,
                    'locked_by' => null,
                ]);
            }

            // Revert pendaftar status back to INTERVIEW if was LULUS/TIDAK_LULUS
            if (in_array($pendaftar->status, [PendaftarStatus::Lulus, PendaftarStatus::TidakLulus], true)) {
                $pendaftar->status = PendaftarStatus::Interview;
                $pendaftar->save();
            }

            // Revert Kelompok status from completed to in_progress
            if ($kelompokId) {
                $kelompok = KelompokUjian::find($kelompokId);
                if ($kelompok && $kelompok->status === StatusKelompokUjian::Completed) {
                    $kelompok->status = StatusKelompokUjian::InProgress;
                    $kelompok->save();
                }
            }
        });

        return back()->with('success', "Kunci nilai untuk calon santri {$pendaftar->nama} berhasil dibuka.");
    }

    /**
     * Display Spreadsheet mass scoring page for a specific Kelompok Ujian.
     */
    public function spreadsheet(KelompokUjian $kelompokUjian): Response
    {
        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.view')) {
            abort(403, 'Anda tidak memiliki hak akses ke spreadsheet kelompok ini.');
        }

        $kelompokUjian->load([
            'pendaftars' => function ($pq) {
                $pq->with([
                    'cabang:id,name',
                    'jenjang:id,code,name,singkatan,logo_path',
                    'penilaians.aspek',
                    'hasilUjian',
                ]);
            },
            'pengujis:id,name,email',
            'koordinator:id,name,email',
        ]);

        $kategoriPenilaians = KategoriPenilaian::with([
            'aspek_penilaians' => function ($aq) {
                $aq->orderBy('urutan', 'asc')->orderBy('created_at', 'asc');
            },
        ])
            ->where('is_active', true)
            ->get();

        return Inertia::render('Admin/Pendaftar/Penilaian/Spreadsheet', [
            'kelompok' => $kelompokUjian,
            'kategoriPenilaians' => $kategoriPenilaians,
        ]);
    }

    /**
     * Generate printable / PDF view of Surat Keterangan Hasil Tes Sementara.
     */
    public function cetakSuratHasil(string $pendaftarId)
    {
        $user = auth()->user();
        $pendaftar = Pendaftar::with([
            'cabang',
            'jenjang',
            'periode.tahunAkademik',
            'gelombang',
            'kelompokUjians.pengujis',
            'hasilUjian',
            'penilaians.aspek.kategori',
        ])->findOrFail($pendaftarId);

        $isAssignedExaminer = $pendaftar->kelompokUjians()->where(function ($kq) use ($user) {
            $kq->whereHas('pengujis', fn ($uq) => $uq->where('users.id', $user->id))
                ->orWhereHas('koordinator', fn ($uq) => $uq->where('users.id', $user->id));
        })->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencetak surat hasil tes calon santri ini.');
        }

        $hasilUjian = $pendaftar->hasilUjian;

        return view('pdf.surat-hasil-tes-sementara', [
            'pendaftar' => $pendaftar,
            'hasilUjian' => $hasilUjian,
            'tanggalSurat' => now()->translatedFormat('d F Y'),
        ]);
    }

    /**
     * Export penilaian list to Excel matching client template Rangkuman.xlsx.
     */
    public function export(Request $request): StreamedResponse
    {
        $selectedIds = $request->input('ids') ? explode(',', $request->input('ids')) : [];
        $selectedJenjangId = $request->input('jenjang_id');
        $search = $request->input('search');
        $cabangId = $request->input('cabang_id');
        $periodeId = $request->input('periode_id');
        $gelombangId = $request->input('gelombang_id');
        $kelompokId = $request->input('kelompok_ujian_id');
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $tahunAkademikId = $activeTahunAkademik?->id;

        $user = auth()->user();

        $query = Pendaftar::query()
            ->with([
                'cabang',
                'jenjang',
                'kelompokUjians.pengujis',
                'penilaians.aspek',
                'hasilUjian',
            ])
            ->whereHas('kelompokUjians');

        if ($tahunAkademikId) {
            $query->whereHas('periode', function ($q) use ($tahunAkademikId) {
                $q->where('tahun_akademik_id', $tahunAkademikId);
            });
        }

        if (! empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } else {
            if ($selectedJenjangId) {
                $query->where('jenjang_id', $selectedJenjangId);
            }
            if ($cabangId) {
                $query->where('cabang_id', $cabangId);
            }
            if ($gelombangId) {
                $query->where('gelombang_id', $gelombangId);
            }
            if ($kelompokId) {
                $query->whereHas('kelompokUjians', fn ($kq) => $kq->where('kelompok_ujians.id', $kelompokId));
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                });
            }
        }

        $candidates = $query->orderBy('created_at', 'asc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Penilaian');

        // Header Title
        $sheet->setCellValue('A1', 'CALON SANTRI BARU - PON-PES DARULLUGHAH WADDA\'WAH');
        $sheet->setCellValue('A2', 'Lembar Hasil Tes dan Wawancara — Wilayah Kalimantan Barat');
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table Header (Row 4 & 5)
        $sheet->setCellValue('A4', 'NO');
        $sheet->setCellValue('B4', 'NOMOR PESERTA');
        $sheet->setCellValue('C4', 'NAMA LENGKAP');
        $sheet->setCellValue('D4', 'JENJANG');
        $sheet->setCellValue('E4', 'HASIL INTERVIEW');
        $sheet->setCellValue('F4', 'TES MEMBACA KITAB');
        $sheet->setCellValue('G4', 'TES MENULIS');
        $sheet->setCellValue('H4', 'TES HAFALAN');
        $sheet->setCellValue('I4', 'TOTAL SKOR');
        $sheet->setCellValue('J4', 'REKOMENDASI KELAS');
        $sheet->setCellValue('K4', 'STATUS KUNCI');
        $sheet->setCellValue('L4', 'CATATAN FINAL');

        $sheet->getStyle('A4:L4')->getFont()->setBold(true);
        $sheet->getStyle('A4:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:L4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');

        $rowNum = 5;
        $no = 1;

        foreach ($candidates as $cand) {
            $hasil = $cand->hasilUjian;

            $sheet->setCellValue("A{$rowNum}", $no++);
            $sheet->setCellValueExplicit("B{$rowNum}", (string) ($cand->nomor_pendaftaran ?? $cand->nik ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$rowNum}", $cand->nama);
            $sheet->setCellValue("D{$rowNum}", $cand->jenjang?->code ?? $cand->jenjang?->name ?? '-');
            $sheet->setCellValue("E{$rowNum}", $hasil?->hasil_wawancara ? "Kualifikasi {$hasil->hasil_wawancara}" : '-');
            $sheet->setCellValue("F{$rowNum}", $hasil?->predikat_baca_kitab ? "{$hasil->nilai_baca_kitab} ({$hasil->predikat_baca_kitab})" : '-');
            $sheet->setCellValue("G{$rowNum}", $hasil?->predikat_menulis ? "{$hasil->nilai_menulis} ({$hasil->predikat_menulis})" : '-');
            $sheet->setCellValue("H{$rowNum}", $hasil?->predikat_hafalan ? "{$hasil->nilai_hafalan} ({$hasil->predikat_hafalan})" : '-');
            $sheet->setCellValue("I{$rowNum}", $hasil?->total_nilai ?? 0);
            $sheet->setCellValue("J{$rowNum}", $hasil?->rekomendasi_kelas_pondok ?? '-');
            $sheet->setCellValue("K{$rowNum}", $hasil?->locked_at ? 'TERKUNCI' : 'DRAFT');
            $sheet->setCellValue("L{$rowNum}", $hasil?->catatan_final ?? '-');

            $rowNum++;
        }

        // Set borders and auto column width
        $lastRow = max(5, $rowNum - 1);
        $sheet->getStyle("A4:L{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Rekap_Penilaian_PSB_Dalwa_'.date('Ymd_His').'.xlsx';

        return response()->stream(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    /**
     * Reset password for candidate.
     */
    public function resetPassword(Request $request, string $pendaftarId): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $pendaftar = Pendaftar::findOrFail($pendaftarId);
        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->can('pendaftar.edit')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk mereset password calon santri ini.');
        }

        $pendaftar->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('success', "Password untuk calon santri {$pendaftar->nama} berhasil direset.");
    }

    /**
     * Display the detail page for a Kelompok Ujian.
     */
    public function showKelompok(KelompokUjian $kelompokUjian): Response
    {
        $user = auth()->user();
        $isAssignedExaminer = $kelompokUjian->pengujis()->where('users.id', $user->id)->exists()
            || $kelompokUjian->koordinator()->where('users.id', $user->id)->exists();

        if (! $user->isSuperAdmin() && ! $isAssignedExaminer && ! $user->can('ujian.penilaian.input') && ! $user->can('pendaftar.view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat kelompok interview ini.');
        }

        $kelompokUjian = KelompokUjian::with([
            'pengujis',
            'koordinator',
            'pendaftars' => function ($pq) use ($kelompokUjian) {
                $pq->with([
                    'jenjang',
                    'cabang',
                    'hasilUjian' => function ($hq) use ($kelompokUjian) {
                        $hq->where(function ($w) use ($kelompokUjian) {
                            $w->where('kelompok_ujian_id', $kelompokUjian->id)
                                ->orWhereNull('kelompok_ujian_id');
                        })->with('dataWawancara');
                    },
                    'dokumens.dokumen',
                    'penilaians.aspek:id,kategori_id,nama_aspek,bobot',
                    'penilaians.aspek.kategori:id,nama_kategori',
                    'penilaians.penguji:id,name',
                ]);
            },
        ])
            ->withCount('pendaftars')
            ->findOrFail($kelompokUjian->id);

        $kelompokUjian->load([
            'pengujis' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email', 'users.nik', 'users.nip', 'users.foto')
                    ->with(['roles:id,name']);
            },
            'koordinator' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email', 'users.nik', 'users.nip', 'users.foto')
                    ->with(['roles:id,name']);
            },
        ]);

        $jenjangs = Jenjang::accessibleBy()->orderBy('created_at', 'asc')->get();

        // Categorize examiners by role from pivot
        $pewawancaraList = $kelompokUjian->pengujis->where('pivot.peran', 'interview')->values();
        $tesMembacaList = $kelompokUjian->pengujis->where('pivot.peran', 'tes_membaca')->values();
        $tesMenulisList = $kelompokUjian->pengujis->where('pivot.peran', 'tes_menulis')->values();
        $tesHafalanList = $kelompokUjian->pengujis->where('pivot.peran', 'tes_hafalan')->values();
        $koordinatorList = $kelompokUjian->koordinator->values();

        // Compute group metrics
        $totalSantri = $kelompokUjian->pendaftars->count();
        $lakiCount = $kelompokUjian->pendaftars->filter(function ($p) {
            $g = strtolower($p->personal_data['jenis_kelamin'] ?? $p->gender ?? '');

            return str_contains($g, 'laki') || $g === 'l';
        })->count();
        $perempuanCount = $kelompokUjian->pendaftars->filter(function ($p) {
            $g = strtolower($p->personal_data['jenis_kelamin'] ?? $p->gender ?? '');

            return str_contains($g, 'perempuan') || $g === 'p';
        })->count();

        $dinilaiCount = 0;
        $lockedCount = 0;
        foreach ($kelompokUjian->pendaftars as $p) {
            if ($p->hasilUjian) {
                if ($p->hasilUjian->locked_at) {
                    $lockedCount++;
                    $dinilaiCount++;
                } elseif ($p->hasilUjian->total_nilai > 0 || ! empty($p->hasilUjian->hasil_wawancara)) {
                    $dinilaiCount++;
                }
            }
        }

        $cabangs = Cabang::accessibleBy()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $kategoriPenilaians = KategoriPenilaian::with([
            'aspek_penilaians' => function ($aq) {
                $aq->orderBy('urutan', 'asc')->orderBy('created_at', 'asc');
            },
        ])
            ->where('is_active', true)
            ->get();

        return Inertia::render('Admin/Pendaftar/PenilaianInterview/Show', [
            'kelompok' => [
                'id' => $kelompokUjian->id,
                'nama_kelompok' => $kelompokUjian->nama_kelompok,
                'tanggal_ujian' => $kelompokUjian->tanggal_ujian ? (is_string($kelompokUjian->tanggal_ujian) ? $kelompokUjian->tanggal_ujian : $kelompokUjian->tanggal_ujian->toDateString()) : '',
                'waktu_mulai' => $kelompokUjian->waktu_mulai ? substr($kelompokUjian->waktu_mulai, 0, 5) : '08:00',
                'waktu_selesai' => $kelompokUjian->waktu_selesai ? substr($kelompokUjian->waktu_selesai, 0, 5) : '12:00',
                'lokasi' => $kelompokUjian->lokasi,
                'status' => $kelompokUjian->status,
                'created_at' => $kelompokUjian->created_at ? $kelompokUjian->created_at->toISOString() : null,
            ],
            'timUjian' => [
                'pewawancara' => $pewawancaraList,
                'tes_membaca' => $tesMembacaList,
                'tes_menulis' => $tesMenulisList,
                'tes_hafalan' => $tesHafalanList,
                'koordinator' => $koordinatorList,
                'pengawas' => $koordinatorList,
            ],
            'metrics' => [
                'total_santri' => $totalSantri,
                'laki_count' => $lakiCount,
                'perempuan_count' => $perempuanCount,
                'dinilai_count' => $dinilaiCount,
                'locked_count' => $lockedCount,
                'belum_dinilai_count' => max(0, $totalSantri - $dinilaiCount),
            ],
            'pendaftars' => $kelompokUjian->pendaftars,
            'jenjangs' => Jenjang::orderBy('created_at', 'asc')->get(),
            'cabangs' => Cabang::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kategoriPenilaians' => $kategoriPenilaians,
            'isAssignedExaminer' => $isAssignedExaminer,
        ]);
    }

    /**
     * Show the edit page for a Kelompok Ujian.
     */
    public function editKelompok(KelompokUjian $kelompokUjian): Response|RedirectResponse
    {
        $isHMinusOneOrMore = $kelompokUjian->tanggal_ujian && Carbon::parse($kelompokUjian->tanggal_ujian)->startOfDay()->greaterThanOrEqualTo(now()->addDay()->startOfDay());
        $hasNoPenilaian = ! $kelompokUjian->penilaians()->exists();
        $isScheduled = $kelompokUjian->status === StatusKelompokUjian::Scheduled;

        if (! $isHMinusOneOrMore || ! $hasNoPenilaian || ! $isScheduled) {
            return redirect()->route('admin.pendaftar.penilaian_interview.index')
                ->with('error', 'Kelompok ujian ini tidak dapat diedit karena sudah memasuki hari H pelaksanaan atau sudah dilakukan penilaian.');
        }

        $kelompokUjian->load([
            'pendaftars' => function ($q) {
                $q->with(['cabang', 'jenjang', 'periode', 'gelombang', 'hasilUjian', 'dokumens.dokumen']);
            },
            'pengujis',
            'koordinator',
        ]);

        $jenjangs = Jenjang::orderBy('created_at', 'asc')->get();
        $cabangs = Cabang::where('is_active', true)->orderBy('name')->get();

        // Candidates eligible for interview who are not in any group
        $availablePendaftars = Pendaftar::query()
            ->where('status', PendaftarStatus::Interview)
            ->doesntHave('kelompokUjians')
            ->with(['cabang', 'jenjang', 'periode', 'gelombang', 'dokumens.dokumen'])
            ->latest('submitted_at')
            ->get();

        $pengujis = User::select('id', 'name', 'email', 'nik', 'nip', 'foto')
            ->with(['roles:id,name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $koordinator = User::select('id', 'name', 'email', 'nik', 'nip', 'foto')
            ->with(['roles:id,name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Group pengujis by role from pivot
        $interviewPengujiIds = $kelompokUjian->pengujis->where('pivot.peran', 'interview')->pluck('id')->values()->all();
        $tesMembacaPengujiIds = $kelompokUjian->pengujis->where('pivot.peran', 'tes_membaca')->pluck('id')->values()->all();
        $tesMenulisPengujiIds = $kelompokUjian->pengujis->where('pivot.peran', 'tes_menulis')->pluck('id')->values()->all();
        $tesHafalanPengujiIds = $kelompokUjian->pengujis->where('pivot.peran', 'tes_hafalan')->pluck('id')->values()->all();
        $koordinatorIds = $kelompokUjian->koordinator->pluck('id')->values()->all();

        return Inertia::render('Admin/Pendaftar/PenilaianInterview/Edit', [
            'kelompokUjian' => [
                'id' => $kelompokUjian->id,
                'nama_kelompok' => $kelompokUjian->nama_kelompok,
                'tanggal_ujian' => $kelompokUjian->tanggal_ujian ? (is_string($kelompokUjian->tanggal_ujian) ? $kelompokUjian->tanggal_ujian : $kelompokUjian->tanggal_ujian->toDateString()) : '',
                'waktu_mulai' => $kelompokUjian->waktu_mulai ? substr($kelompokUjian->waktu_mulai, 0, 5) : '08:00',
                'waktu_selesai' => $kelompokUjian->waktu_selesai ? substr($kelompokUjian->waktu_selesai, 0, 5) : '12:00',
                'lokasi' => $kelompokUjian->lokasi,
                'status' => $kelompokUjian->status,
                'interview_penguji_ids' => $interviewPengujiIds,
                'tes_membaca_penguji_ids' => $tesMembacaPengujiIds,
                'tes_menulis_penguji_ids' => $tesMenulisPengujiIds,
                'tes_hafalan_penguji_ids' => $tesHafalanPengujiIds,
                'koordinator_ids' => $koordinatorIds,
                'pengawas_ids' => $koordinatorIds,
            ],
            'assignedPendaftars' => $kelompokUjian->pendaftars,
            'availablePendaftars' => $availablePendaftars,
            'jenjangs' => $jenjangs,
            'cabangs' => $cabangs,
            'pengujis' => $pengujis,
            'koordinator' => $koordinator,
            'pengawas' => $koordinator,
        ]);
    }

    /**
     * Update an existing Kelompok Ujian and its assigned candidates & examiners.
     */
    public function updateKelompok(Request $request, KelompokUjian $kelompokUjian): RedirectResponse
    {
        $isHMinusOneOrMore = $kelompokUjian->tanggal_ujian && Carbon::parse($kelompokUjian->tanggal_ujian)->startOfDay()->greaterThanOrEqualTo(now()->addDay()->startOfDay());
        $hasNoPenilaian = ! $kelompokUjian->penilaians()->exists();
        $isScheduled = $kelompokUjian->status === StatusKelompokUjian::Scheduled;

        if (! $isHMinusOneOrMore || ! $hasNoPenilaian || ! $isScheduled) {
            throw ValidationException::withMessages([
                'kelompok' => 'Kelompok ujian tidak dapat diedit karena sudah memasuki hari H pelaksanaan atau sudah dilakukan penilaian.',
            ]);
        }

        $validated = $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'tanggal_ujian' => 'required|date',
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required',
            'lokasi' => 'required|string|max:255',
            'interview_penguji_ids' => 'required|array|min:1|max:1',
            'interview_penguji_ids.*' => 'exists:users,id',
            'tes_membaca_penguji_ids' => 'required|array|min:1|max:1',
            'tes_membaca_penguji_ids.*' => 'exists:users,id',
            'tes_menulis_penguji_ids' => 'required|array|min:1|max:1',
            'tes_menulis_penguji_ids.*' => 'exists:users,id',
            'tes_hafalan_penguji_ids' => 'required|array|min:1|max:1',
            'tes_hafalan_penguji_ids.*' => 'exists:users,id',
            'koordinator_ids' => 'nullable|array|max:1',
            'koordinator_ids.*' => 'exists:users,id',
            'pengawas_ids' => 'nullable|array|max:1',
            'pengawas_ids.*' => 'exists:users,id',
            'pendaftar_ids' => 'required|array|min:1',
            'pendaftar_ids.*' => 'exists:pendaftars,id',
        ], [
            'nama_kelompok.required' => 'Nama kelompok ujian wajib diisi.',
            'tanggal_ujian.required' => 'Tanggal pelaksanaan ujian wajib diisi.',
            'waktu_mulai.required' => 'Waktu mulai ujian wajib diisi.',
            'waktu_selesai.required' => 'Waktu selesai ujian wajib diisi.',
            'lokasi.required' => 'Ruangan / lokasi ujian wajib diisi.',
            'interview_penguji_ids.required' => 'Wajib memilih 1 orang pewawancara untuk sesi Interview.',
            'interview_penguji_ids.min' => 'Wajib memilih 1 orang pewawancara untuk sesi Interview.',
            'interview_penguji_ids.max' => 'Pewawancara (Interview) hanya boleh dipilih 1 orang.',
            'tes_membaca_penguji_ids.required' => 'Wajib memilih 1 orang penguji untuk Tes Membaca.',
            'tes_membaca_penguji_ids.min' => 'Wajib memilih 1 orang penguji untuk Tes Membaca.',
            'tes_membaca_penguji_ids.max' => 'Penguji Tes Membaca hanya boleh dipilih 1 orang.',
            'tes_menulis_penguji_ids.required' => 'Wajib memilih 1 orang penguji untuk Tes Menulis.',
            'tes_menulis_penguji_ids.min' => 'Wajib memilih 1 orang penguji untuk Tes Menulis.',
            'tes_menulis_penguji_ids.max' => 'Penguji Tes Menulis hanya boleh dipilih 1 orang.',
            'tes_hafalan_penguji_ids.required' => 'Wajib memilih 1 orang penguji untuk Tes Hafalan.',
            'tes_hafalan_penguji_ids.min' => 'Wajib memilih 1 orang penguji untuk Tes Hafalan.',
            'tes_hafalan_penguji_ids.max' => 'Penguji Tes Hafalan hanya boleh dipilih 1 orang.',
            'koordinator_ids.max' => 'Koordinator PSB hanya boleh dipilih 1 orang.',
            'pendaftar_ids.required' => 'Wajib memilih minimal 1 calon santri untuk kelompok ini.',
            'pendaftar_ids.min' => 'Wajib memilih minimal 1 calon santri untuk kelompok ini.',
        ]);

        return DB::transaction(function () use ($validated, $kelompokUjian, $request) {
            $kelompokUjian->update([
                'nama_kelompok' => $validated['nama_kelompok'],
                'tanggal_ujian' => $validated['tanggal_ujian'],
                'waktu_mulai' => $validated['waktu_mulai'],
                'waktu_selesai' => $validated['waktu_selesai'],
                'lokasi' => $validated['lokasi'],
            ]);

            // Sync pengujis with roles
            $kelompokUjian->pengujis()->detach();

            foreach ($validated['interview_penguji_ids'] as $uid) {
                $kelompokUjian->pengujis()->attach($uid, ['peran' => 'interview']);
            }
            foreach ($validated['tes_membaca_penguji_ids'] as $uid) {
                $kelompokUjian->pengujis()->attach($uid, ['peran' => 'tes_membaca']);
            }
            foreach ($validated['tes_menulis_penguji_ids'] as $uid) {
                $kelompokUjian->pengujis()->attach($uid, ['peran' => 'tes_menulis']);
            }
            foreach ($validated['tes_hafalan_penguji_ids'] as $uid) {
                $kelompokUjian->pengujis()->attach($uid, ['peran' => 'tes_hafalan']);
            }

            $koordinatorIds = $validated['koordinator_ids'] ?? $validated['pengawas_ids'] ?? null;
            if ($koordinatorIds !== null) {
                $kelompokUjian->koordinator()->sync($koordinatorIds);
            } else {
                $kelompokUjian->koordinator()->detach();
            }

            // Sync candidates
            $oldPendaftarIds = $kelompokUjian->pendaftars()->pluck('pendaftars.id')->toArray();
            $newPendaftarIds = $validated['pendaftar_ids'];

            $removedIds = array_diff($oldPendaftarIds, $newPendaftarIds);
            $addedIds = array_diff($newPendaftarIds, $oldPendaftarIds);

            // Revert removed candidates to INTERVIEW and remove temporary scores
            if (! empty($removedIds)) {
                Pendaftar::whereIn('id', $removedIds)->update([
                    'status' => PendaftarStatus::Interview,
                ]);
                Penilaian::where('kelompok_ujian_id', $kelompokUjian->id)->whereIn('pendaftar_id', $removedIds)->delete();
                HasilUjian::whereIn('pendaftar_id', $removedIds)->delete();
            }

            // Ensure new candidates have status INTERVIEW
            if (! empty($addedIds)) {
                Pendaftar::whereIn('id', $addedIds)->update([
                    'status' => PendaftarStatus::Interview,
                ]);
            }

            // Sync pivot
            $kelompokUjian->pendaftars()->sync($newPendaftarIds);

            activity()
                ->useLog('Pendaftar Penilaian')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'kelompok_id' => $kelompokUjian->id,
                    'kelompok_name' => $kelompokUjian->nama_kelompok,
                    'total_assigned' => count($newPendaftarIds),
                    'added_count' => count($addedIds),
                    'removed_count' => count($removedIds),
                ])
                ->log("Memperbarui data kelompok interview '{$kelompokUjian->nama_kelompok}'");

            return redirect()->route('admin.pendaftar.penilaian_interview.index')
                ->with('success', "Data kelompok interview '{$kelompokUjian->nama_kelompok}' berhasil diperbarui.");
        });
    }

    /**
     * Delete a Kelompok Ujian and revert its candidates' status to INTERVIEW (Belum Dijadwalkan).
     */
    public function destroyKelompok(Request $request, KelompokUjian $kelompokUjian): RedirectResponse
    {
        $isHMinusOneOrMore = $kelompokUjian->tanggal_ujian && Carbon::parse($kelompokUjian->tanggal_ujian)->startOfDay()->greaterThanOrEqualTo(now()->addDay()->startOfDay());
        $hasNoPenilaian = ! $kelompokUjian->penilaians()->exists();
        $isScheduled = $kelompokUjian->status === StatusKelompokUjian::Scheduled;

        if (! $isHMinusOneOrMore || ! $hasNoPenilaian || ! $isScheduled) {
            return back()->with('error', 'Kelompok ujian ini tidak dapat dihapus karena sudah memasuki hari H pelaksanaan atau sudah dilakukan penilaian.');
        }

        DB::transaction(function () use ($kelompokUjian, $request) {
            $kelompokUjian->load('pendaftars');
            $pendaftarIds = $kelompokUjian->pendaftars->pluck('id')->toArray();
            $namaKelompok = $kelompokUjian->nama_kelompok;

            // Revert candidate status to INTERVIEW and delete any exam scores for this kelompok
            if (! empty($pendaftarIds)) {
                Pendaftar::whereIn('id', $pendaftarIds)->update([
                    'status' => PendaftarStatus::Interview,
                ]);

                // Also delete related scores & hasil ujian for these candidates under this kelompok
                Penilaian::where('kelompok_ujian_id', $kelompokUjian->id)->delete();
                HasilUjian::whereIn('pendaftar_id', $pendaftarIds)->delete();
            }

            // Detach relationships
            $kelompokUjian->pendaftars()->detach();
            $kelompokUjian->pengujis()->detach();
            $kelompokUjian->koordinator()->detach();

            // Delete the kelompok record
            $kelompokUjian->delete();

            activity()
                ->useLog('Pendaftar Penilaian')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'kelompok_name' => $namaKelompok,
                    'total_reverted_candidates' => count($pendaftarIds),
                ])
                ->log("Menghapus kelompok interview '{$namaKelompok}' dan mengembalikan ".count($pendaftarIds).' calon santri ke status Belum Dijadwalkan');
        });

        return back()->with('success', "Kelompok interview {$kelompokUjian->nama_kelompok} berhasil dihapus dan seluruh santri dikembalikan ke status belum dijadwalkan.");
    }

    /**
     * Delete a single candidate from scoring list.
     */
    public function destroy(string $pendaftarId): RedirectResponse
    {
        $pendaftar = Pendaftar::findOrFail($pendaftarId);
        $pendaftar->delete();

        return back()->with('success', "Data calon santri {$pendaftar->nama} berhasil dihapus.");
    }

    /**
     * Bulk delete candidates from scoring list.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|exists:pendaftars,id',
        ]);

        $ids = $request->input('ids', []);
        Pendaftar::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids).' data calon santri berhasil dihapus.');
    }

    /**
     * Recalculate HasilUjian summary scores and predicates for a candidate.
     */
    protected function recalculateHasilUjian(string $pendaftarId, string $kelompokUjianId): void
    {
        $penilaians = Penilaian::with('aspek.kategori')
            ->where('pendaftar_id', $pendaftarId)
            ->where('kelompok_ujian_id', $kelompokUjianId)
            ->get();

        $scoresByCategory = [];
        foreach ($penilaians as $p) {
            $kategoriName = strtolower($p->aspek?->kategori?->nama_kategori ?? '');
            $bobot = (float) ($p->aspek?->bobot ?? 0);
            $nilai = (float) $p->nilai;

            if (str_contains($kategoriName, 'hafal') || str_contains($kategoriName, 'tahfidz')) {
                $scoresByCategory['hafalan'][] = ['nilai' => $nilai, 'bobot' => $bobot];
            } elseif (str_contains($kategoriName, 'tulis') || str_contains($kategoriName, 'menulis') || str_contains($kategoriName, 'imla')) {
                $scoresByCategory['menulis'][] = ['nilai' => $nilai, 'bobot' => $bobot];
            } elseif (str_contains($kategoriName, 'baca') || str_contains($kategoriName, 'kitab')) {
                $scoresByCategory['baca_kitab'][] = ['nilai' => $nilai, 'bobot' => $bobot];
            }
        }

        $calcCategory = function ($items) {
            if (empty($items)) {
                return ['nilai' => 0, 'predikat' => null];
            }
            $sum = array_sum(array_column($items, 'nilai'));
            $nilai = round($sum, 2);

            $predikat = 'KURANG';
            if ($nilai >= 86) {
                $predikat = 'BAIK SEKALI';
            } elseif ($nilai >= 71) {
                $predikat = 'BAIK';
            } elseif ($nilai >= 56) {
                $predikat = 'CUKUP';
            }

            return ['nilai' => $nilai, 'predikat' => $predikat];
        };

        $resMenulis = $calcCategory($scoresByCategory['menulis'] ?? []);
        $resBaca = $calcCategory($scoresByCategory['baca_kitab'] ?? []);
        $resHafalan = $calcCategory($scoresByCategory['hafalan'] ?? []);

        $activeCategoriesCount = 0;
        $totalSum = 0;
        if (! empty($scoresByCategory['menulis'])) {
            $totalSum += $resMenulis['nilai'];
            $activeCategoriesCount++;
        }
        if (! empty($scoresByCategory['baca_kitab'])) {
            $totalSum += $resBaca['nilai'];
            $activeCategoriesCount++;
        }
        if (! empty($scoresByCategory['hafalan'])) {
            $totalSum += $resHafalan['nilai'];
            $activeCategoriesCount++;
        }

        $overallTotal = $activeCategoriesCount > 0 ? round($totalSum / $activeCategoriesCount, 2) : 0;

        $hasilUjian = HasilUjian::firstOrNew([
            'pendaftar_id' => $pendaftarId,
            'kelompok_ujian_id' => $kelompokUjianId,
        ]);
        $hasilUjian->nilai_menulis = $resMenulis['nilai'];
        $hasilUjian->predikat_menulis = $resMenulis['predikat'];
        $hasilUjian->nilai_baca_kitab = $resBaca['nilai'];
        $hasilUjian->predikat_baca_kitab = $resBaca['predikat'];
        $hasilUjian->nilai_hafalan = $resHafalan['nilai'];
        $hasilUjian->predikat_hafalan = $resHafalan['predikat'];
        $hasilUjian->total_nilai = $overallTotal;
        $hasilUjian->save();
    }

    /**
     * Get roman month string.
     */
    protected function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }
}
