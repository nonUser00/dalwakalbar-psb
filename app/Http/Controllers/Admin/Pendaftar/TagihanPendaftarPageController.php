<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Enums\PendaftarStatus;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPeriode;
use App\Exports\TagihanPendaftarExport;
use App\Http\Controllers\Controller;
use App\Models\Keuangan\Bank;
use App\Models\Keuangan\KategoriBiaya;
use App\Models\Keuangan\Pembayaran;
use App\Models\Keuangan\Tagihan;
use App\Models\Keuangan\TagihanItem;
use App\Models\Keuangan\VirtualAccount;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftaran\Gelombang;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TagihanPendaftarPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pendaftar.view', only: ['index', 'create', 'showDetail']),
            new Middleware('permission:pendaftar.edit', only: ['createBill', 'destroyBill', 'addPayment', 'editPayment', 'verifyPayment', 'resetPassword', 'deletePayment', 'bulkDeletePayments']),
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
        $statusPembuatan = $request->query('status_pembuatan_tagihan') ?: $request->query('status_pembuatan');
        $statusTagihan = $request->query('status_tagihan');
        $statusPembayaran = $request->query('status_pembayaran');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // All active Jenjangs accessible by user ordered (MTs, MA, S1, S2, S3)
        $jenjangs = Jenjang::accessibleBy()->orderBy('created_at', 'asc')->get();

        // Selected active jenjang (ensure valid for allowed jenjangs)
        $selectedJenjangId = $request->query('jenjang_id');
        if ($selectedJenjangId && ! $jenjangs->contains('id', $selectedJenjangId)) {
            $selectedJenjangId = null;
        }
        if (! $selectedJenjangId && $jenjangs->isNotEmpty()) {
            $selectedJenjangId = $jenjangs->first()->id;
        }

        // Get Active Academic Year
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $hasActiveTahunAkademik = $activeTahunAkademik !== null;

        if (! $hasActiveTahunAkademik) {
            $badgeCounts = [];
            foreach ($jenjangs as $j) {
                $badgeCounts[$j->id] = [
                    'total' => 0,
                    'sudah_dibuat' => 0,
                    'belum_dibuat' => 0,
                    'menunggu_verifikasi' => 0,
                ];
            }

            return Inertia::render('Admin/Pendaftar/Tagihan/Index', [
                'pendaftars' => Pendaftar::whereRaw('1 = 0')->paginate($limit)->withQueryString(),
                'jenjangs' => $jenjangs,
                'jenjangCounts' => $badgeCounts,
                'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
                'cabangs' => Cabang::accessibleBy()->orderBy('name')->get(),
                'activeTahunAkademik' => null,
                'hasActiveTahunAkademik' => false,
                'gelombangs' => [],
                'banks' => Bank::where('is_active', true)->orderBy('name')->get(),
                'tagihanTemplates' => TagihanItem::latest('created_at')->get(),
                'kategoriBiayas' => KategoriBiaya::with('itemBiayas')->get(),
                'filters' => [
                    'search' => (string) ($search ?? ''),
                    'limit' => $limit,
                    'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                    'cabang_id' => (string) ($cabangId ?? ''),
                    'gelombang_id' => (string) ($gelombangId ?? ''),
                    'gender' => (string) ($gender ?? ''),
                    'status_pembuatan_tagihan' => (string) ($statusPembuatan ?? ''),
                    'status_tagihan' => (string) ($statusTagihan ?? ''),
                    'status_pembayaran' => (string) ($statusPembayaran ?? ''),
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

        // Count per jenjang for TAGIHAN status (scoped strictly to active academic year and selected wave)
        $jenjangCounts = [];
        foreach ($jenjangs as $j) {
            $baseJenjangQuery = Pendaftar::accessibleBy()
                ->where('status', PendaftarStatus::Tagihan)
                ->where('jenjang_id', $j->id)
                ->whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
                ->when($gelombangId, fn ($q) => $q->where('gelombang_id', $gelombangId));

            $total = (clone $baseJenjangQuery)->count();
            $sudahDibuat = (clone $baseJenjangQuery)->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)
                        ->orWhereNull('is_interview_ulang');
                })->has('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)
                            ->whereHas('tagihans', function ($tq) {
                                $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                    });
            })->count();

            $belumDibuat = (clone $baseJenjangQuery)->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)
                        ->orWhereNull('is_interview_ulang');
                })->doesntHave('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)
                            ->whereDoesntHave('tagihans', function ($tq) {
                                $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                    });
            })->count();

            $menungguVerifikasi = (clone $baseJenjangQuery)
                ->whereHas('tagihans.pembayarans', function ($q) {
                    $q->where('status', StatusPembayaran::MenungguVerifikasi->value);
                })
                ->count();

            $jenjangCounts[$j->id] = [
                'total' => $total,
                'sudah_dibuat' => $sudahDibuat,
                'belum_dibuat' => $belumDibuat,
                'menunggu_verifikasi' => $menungguVerifikasi,
            ];
        }

        // Main Query (strictly scoped to active academic year)
        $query = Pendaftar::accessibleBy()
            ->where('status', PendaftarStatus::Tagihan)
            ->whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
            ->with([
                'cabang',
                'jenjang',
                'periode',
                'gelombang',
                'dokumens.dokumen',
                'virtualAccounts.bank',
                'tagihans' => function ($q) {
                    $q->latest('created_at')->with([
                        'items',
                        'pembayarans' => function ($pq) {
                            $pq->latest('created_at')->with(['bank', 'verifier']);
                        },
                        'creator',
                    ]);
                },
            ]);

        if ($selectedJenjangId) {
            $query->where('jenjang_id', $selectedJenjangId);
        }

        if ($gelombangId) {
            $query->where('gelombang_id', $gelombangId);
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

        if ($statusPembuatan === 'dibuat') {
            $query->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)
                        ->orWhereNull('is_interview_ulang');
                })->has('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)
                            ->whereHas('tagihans', function ($tq) {
                                $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                    });
            });
        } elseif ($statusPembuatan === 'belum') {
            $query->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)
                        ->orWhereNull('is_interview_ulang');
                })->doesntHave('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)
                            ->whereDoesntHave('tagihans', function ($tq) {
                                $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                    });
            });
        }

        if ($statusTagihan) {
            $query->whereHas('tagihans', function ($q) use ($statusTagihan) {
                $q->where('status', $statusTagihan);
            });
        }

        if ($statusPembayaran) {
            $query->whereHas('tagihans.pembayarans', function ($q) use ($statusPembayaran) {
                $q->where('status', $statusPembayaran);
            });
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $pendaftars = $query->latest('submitted_at')->latest('created_at')->paginate($limit)->withQueryString();

        // Master options for filters and modals
        $cabangs = Cabang::accessibleBy()->orderBy('name')->get();
        $banks = Bank::where('is_active', true)->orderBy('name')->get();
        $kategoriBiayas = KategoriBiaya::with('itemBiayas')->get();

        return Inertia::render('Admin/Pendaftar/Tagihan/Index', [
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
            'banks' => $banks,
            'tagihanTemplates' => TagihanItem::latest('created_at')->get(),
            'kategoriBiayas' => $kategoriBiayas,
            'filters' => [
                'search' => (string) ($search ?? ''),
                'limit' => $limit,
                'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                'cabang_id' => (string) ($cabangId ?? ''),
                'gelombang_id' => (string) ($gelombangId ?? ''),
                'gender' => (string) ($gender ?? ''),
                'status_pembuatan_tagihan' => (string) ($statusPembuatan ?? ''),
                'status_tagihan' => (string) ($statusTagihan ?? ''),
                'status_pembayaran' => (string) ($statusPembayaran ?? ''),
                'start_date' => (string) ($startDate ?? ''),
                'end_date' => (string) ($endDate ?? ''),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $jenjangs = Jenjang::accessibleBy()->orderBy('code')->get();
        $cabangs = Cabang::accessibleBy()->orderBy('name')->get();

        $idsParam = $request->query('ids') ?: $request->query('pendaftar_id');
        $ids = [];
        if ($idsParam) {
            $ids = is_array($idsParam) ? $idsParam : explode(',', $idsParam);
        }

        $jenjangId = $request->query('jenjang_id');
        if (! $jenjangId && ! empty($ids)) {
            $firstPendaftar = Pendaftar::accessibleBy()->whereIn('id', $ids)->first();
            $jenjangId = $firstPendaftar?->jenjang_id;
        }

        $activeJenjang = $jenjangs->firstWhere('id', $jenjangId) ?: $jenjangs->first();

        // Target pendaftars strictly MUST be status TAGIHAN, without tagihans in current cycle, and matching active jenjang
        $rawPendaftars = Pendaftar::accessibleBy()
            ->whereIn('id', $ids)
            ->where('status', PendaftarStatus::Tagihan)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)
                        ->orWhereNull('is_interview_ulang');
                })->doesntHave('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)
                            ->whereDoesntHave('tagihans', function ($tq) {
                                $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                    });
            })
            ->when($activeJenjang, fn ($q) => $q->where('jenjang_id', $activeJenjang->id))
            ->with(['cabang', 'jenjang', 'periode', 'gelombang', 'tagihans', 'dokumens.dokumen'])
            ->get();

        // Determine mode: if there are standard pendaftaran, priority is 'pendaftaran'. If only interview ulang, mode is 'interview'.
        $hasRegular = $rawPendaftars->contains(fn ($p) => ! $p->is_interview_ulang);
        $hasInterview = $rawPendaftars->contains(fn ($p) => (bool) $p->is_interview_ulang);

        $tagihanType = 'pendaftaran';
        if (! $hasRegular && $hasInterview) {
            $tagihanType = 'interview';
        }

        // Filter target pendaftars to match the determined tagihanType
        $pendaftars = $rawPendaftars->filter(function ($p) use ($tagihanType) {
            return $tagihanType === 'interview' ? (bool) $p->is_interview_ulang : ! $p->is_interview_ulang;
        })->values();

        // Load kategori biayas strictly for the determined tagihanType matching activeJenjang
        $kategoriBiayasQuery = KategoriBiaya::with(['itemBiayas', 'jenjang'])
            ->where('jenis', $tagihanType)
            ->where(function ($q) use ($activeJenjang) {
                $q->whereNull('jenjang_id')
                    ->orWhere('jenjang_id', $activeJenjang?->id);
            });

        if ($kategoriBiayasQuery->count() === 0) {
            $kategoriBiayasQuery = KategoriBiaya::with(['itemBiayas', 'jenjang'])
                ->where('jenis', $tagihanType);
        }

        $kategoriBiayas = $kategoriBiayasQuery
            ->orderBy('name')
            ->get()
            ->map(function ($kat) {
                $totalBiaya = (float) $kat->itemBiayas->sum('nominal');

                return [
                    'id' => $kat->id,
                    'jenis' => $kat->jenis,
                    'name' => $kat->name,
                    'jenjang_id' => $kat->jenjang_id,
                    'jenjang_code' => $kat->jenjang?->code,
                    'total_biaya' => $totalBiaya,
                    'items' => $kat->itemBiayas->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'nominal' => (float) $item->nominal,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

        // Calculate jenjang stats from DB
        $jenjangStat = [
            'total_pendaftar' => Pendaftar::accessibleBy()->where('status', PendaftarStatus::Tagihan)->where('jenjang_id', $activeJenjang?->id)->count(),
            'sudah_dibuat' => Pendaftar::accessibleBy()->where('status', PendaftarStatus::Tagihan)->where('jenjang_id', $activeJenjang?->id)->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                })->has('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)->whereHas('tagihans', function ($tq) {
                            $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                        });
                    });
            })->count(),
            'belum_dibuat' => Pendaftar::accessibleBy()->where('status', PendaftarStatus::Tagihan)->where('jenjang_id', $activeJenjang?->id)->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                })->doesntHave('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)->whereDoesntHave('tagihans', function ($tq) {
                            $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                        });
                    });
            })->count(),
        ];

        // Count per jenjang for TAGIHAN status
        $jenjangCounts = [];
        foreach ($jenjangs as $j) {
            $total = Pendaftar::accessibleBy()->where('status', PendaftarStatus::Tagihan)->where('jenjang_id', $j->id)->count();
            $sudahDibuat = Pendaftar::accessibleBy()->where('status', PendaftarStatus::Tagihan)->where('jenjang_id', $j->id)->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                })->has('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)->whereHas('tagihans', function ($tq) {
                            $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                        });
                    });
            })->count();
            $belumDibuat = Pendaftar::accessibleBy()->where('status', PendaftarStatus::Tagihan)->where('jenjang_id', $j->id)->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                })->doesntHave('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)->whereDoesntHave('tagihans', function ($tq) {
                            $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                        });
                    });
            })->count();

            $jenjangCounts[$j->id] = [
                'total' => $total,
                'sudah_dibuat' => $sudahDibuat,
                'belum_dibuat' => $belumDibuat,
            ];
        }

        // Available pendaftars strictly MUST be status TAGIHAN, without tagihans in current cycle, matching active jenjang, and matching the determined tagihanType
        $availablePendaftars = Pendaftar::accessibleBy()
            ->where('status', PendaftarStatus::Tagihan)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                })->doesntHave('tagihans')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)->whereDoesntHave('tagihans', function ($tq) {
                            $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                        });
                    });
            })
            ->when($tagihanType === 'interview', function ($q) {
                $q->where('is_interview_ulang', true);
            }, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                });
            })
            ->when($activeJenjang, fn ($q) => $q->where('jenjang_id', $activeJenjang->id))
            ->with(['cabang', 'jenjang', 'periode', 'dokumens.dokumen'])
            ->orderBy('nama')
            ->get();

        return Inertia::render('Admin/Pendaftar/Tagihan/Create', [
            'targetPendaftars' => $pendaftars,
            'selectedIds' => $pendaftars->pluck('id')->all(),
            'cabangs' => $cabangs,
            'jenjangs' => $jenjangs,
            'activeJenjang' => $activeJenjang,
            'tagihanType' => $tagihanType,
            'jenjangStat' => $jenjangStat,
            'jenjangCounts' => $jenjangCounts,
            'kategoriBiayas' => $kategoriBiayas,
            'availablePendaftars' => $availablePendaftars,
        ]);
    }

    public function showDetail(Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(Auth::user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data calon santri ini.');
        }

        $pendaftar->load([
            'cabang',
            'jenjang',
            'periode.tahunAkademik',
            'gelombang',
            'dokumens.dokumen',
            'virtualAccounts.bank',
            'tagihans' => function ($q) {
                $q->latest('created_at')->with([
                    'items',
                    'pembayarans' => function ($pq) {
                        $pq->latest('created_at')->with(['bank', 'verifier', 'creator']);
                    },
                ]);
            },
        ]);

        $banks = Bank::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/Pendaftar/Tagihan/Show', [
            'pendaftar' => $pendaftar,
            'tagihan' => $pendaftar->tagihans->first(),
            'banks' => $banks,
        ]);
    }

    public function createBill(Request $request)
    {
        $validated = $request->validate([
            'pendaftar_ids' => 'required|array|min:1',
            'pendaftar_ids.*' => 'required|string|exists:pendaftars,id',
            'kategori_id' => 'nullable|string',
            'nama_tagihan' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'published_at' => 'nullable|date',
            'samaha_items' => 'nullable|array',
            'samaha_items.*.pendaftar_id' => 'required_with:samaha_items|string',
            'samaha_items.*.amount' => 'nullable|numeric|min:0',
            'samaha_items.*.notes' => 'nullable|string|max:255',
        ], [
            'pendaftar_ids.required' => 'Pilih setidaknya satu calon santri.',
            'nama_tagihan.required' => 'Nama tagihan wajib diisi.',
            'total_amount.required' => 'Nominal tagihan wajib diisi.',
        ]);

        $year = date('Y');
        $count = 0;

        DB::transaction(function () use ($validated, $request, $year, &$count) {
            $samahaMap = [];
            if (! empty($validated['samaha_items'])) {
                foreach ($validated['samaha_items'] as $s) {
                    $samahaMap[$s['pendaftar_id']] = [
                        'amount' => (float) ($s['amount'] ?? 0),
                        'notes' => $s['notes'] ?? 'Potongan Samaha',
                    ];
                }
            }

            $kategori = ! empty($validated['kategori_id'])
                ? KategoriBiaya::with('itemBiayas')->find($validated['kategori_id'])
                : null;

            foreach ($validated['pendaftar_ids'] as $pendaftarId) {
                $pendaftar = Pendaftar::find($pendaftarId);
                if (! $pendaftar || ! $pendaftar->isAccessibleBy(auth()->user())) {
                    continue;
                }

                // Check if already has an active tagihan for the current cycle
                $existing = Tagihan::where('pendaftar_id', $pendaftarId)
                    ->when($pendaftar->is_interview_ulang && $pendaftar->interview_ulang_at, function ($q) use ($pendaftar) {
                        $q->where('created_at', '>=', $pendaftar->interview_ulang_at);
                    })
                    ->first();
                if ($existing) {
                    continue; // Skip if already created for current cycle
                }

                $invoiceNumber = 'INV/'.$year.'/'.strtoupper(Str::random(6)).'/'.rand(1000, 9999);
                $totalAmount = (float) $validated['total_amount'];
                $samahaData = $samahaMap[$pendaftarId] ?? ['amount' => 0, 'notes' => ''];
                $samahaAmount = $samahaData['amount'];

                $tagihanStatus = 'BELUM_BAYAR';
                if ($samahaAmount >= $totalAmount && $totalAmount > 0) {
                    $tagihanStatus = 'SAMAHA';
                } elseif ($samahaAmount > 0) {
                    $tagihanStatus = 'BELUM_LUNAS';
                }

                $tagihan = Tagihan::create([
                    'nomor_invoice' => $invoiceNumber,
                    'pendaftar_id' => $pendaftarId,
                    'total_amount' => $totalAmount,
                    'status' => $tagihanStatus,
                    'due_date' => $validated['due_date'] ?? now()->addDays(14)->toDateString(),
                    'published_at' => $validated['published_at'] ?? now(),
                    'created_by' => auth()->id(),
                ]);

                if ($kategori && $kategori->itemBiayas->isNotEmpty()) {
                    foreach ($kategori->itemBiayas as $item) {
                        TagihanItem::create([
                            'tagihan_id' => $tagihan->id,
                            'item_biaya_id' => $item->id,
                            'name' => $item->name,
                            'amount' => (float) $item->nominal,
                        ]);
                    }
                } else {
                    TagihanItem::create([
                        'tagihan_id' => $tagihan->id,
                        'name' => $validated['nama_tagihan'],
                        'amount' => $totalAmount,
                    ]);
                }

                if ($samahaAmount > 0) {
                    Pembayaran::create([
                        'tagihan_id' => $tagihan->id,
                        'pendaftar_id' => $pendaftarId,
                        'payment_method' => 'SAMAHA',
                        'amount' => $samahaAmount,
                        'payment_date' => now()->toDateString(),
                        'status' => 'DITERIMA',
                        'catatan' => ! empty($samahaData['notes']) ? $samahaData['notes'] : 'Potongan Samaha',
                        'verified_by' => auth()->id(),
                        'created_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);

                    $this->recalculateTagihanStatus($tagihan);
                } elseif ($totalAmount <= 0) {
                    $this->recalculateTagihanStatus($tagihan);
                }

                $count++;
            }

            activity()
                ->useLog('Keuangan')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'count' => $count,
                    'nama_tagihan' => $validated['nama_tagihan'],
                    'total_amount' => $validated['total_amount'],
                ])
                ->log("Menerbitkan tagihan '{$validated['nama_tagihan']}' untuk {$count} calon santri.");
        });

        $firstPendaftar = Pendaftar::find($validated['pendaftar_ids'][0] ?? null);
        $jenjangId = $firstPendaftar?->jenjang_id ?? $kategori?->jenjang_id;

        $redirectUrl = route('admin.pendaftar.tagihan.index');
        if ($jenjangId) {
            $redirectUrl .= '?jenjang_id='.$jenjangId;
        }

        return redirect($redirectUrl)->with('success', "Berhasil menerbitkan tagihan untuk {$count} calon santri.");
    }

    public function destroyBill(Request $request, Tagihan $tagihan)
    {
        if (! $tagihan->pendaftar?->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $hasVerifiedPayment = $tagihan->pembayarans()->where('status', 'DITERIMA')->exists();

        if ($hasVerifiedPayment) {
            return back()->withErrors(['message' => 'Tagihan yang sudah memiliki pembayaran terverifikasi tidak dapat dihapus langsung.']);
        }

        DB::transaction(function () use ($tagihan, $request) {
            $invoiceNumber = $tagihan->nomor_invoice;
            $pendaftarName = $tagihan->pendaftar?->nama;

            $tagihan->items()->delete();
            $tagihan->pembayarans()->delete();
            $tagihan->delete();

            activity()
                ->useLog('Keuangan')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'nomor_invoice' => $invoiceNumber,
                ])
                ->log("Menghapus tagihan {$invoiceNumber} untuk calon santri {$pendaftarName}");
        });

        return back()->with('success', 'Tagihan berhasil dihapus.');
    }

    public function addPayment(Request $request, Tagihan $tagihan)
    {
        if (! $tagihan->pendaftar?->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:TUNAI,SAMAHA,TRANSFER',
            'amount' => 'required|numeric|min:1',
            'bank_id' => 'nullable|string|exists:banks,id',
            'payment_date' => 'required|date',
            'catatan' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.min' => 'Nominal pembayaran minimal Rp 1.',
        ]);

        $otherPaid = (float) $tagihan->pembayarans()
            ->where('status', 'DITERIMA')
            ->sum('amount');

        $maxAllowed = (float) $tagihan->total_amount - $otherPaid;

        if ((float) $validated['amount'] > $maxAllowed) {
            return back()->withErrors([
                'amount' => 'Nominal pembayaran (Rp '.number_format((float) $validated['amount'], 0, ',', '.').') melebihi sisa tagihan (Rp '.number_format(max(0, $maxAllowed), 0, ',', '.').').',
            ]);
        }

        DB::transaction(function () use ($tagihan, $validated, $request) {
            $nomorVa = null;
            if ($validated['payment_method'] === 'TRANSFER' && ! empty($validated['bank_id'])) {
                $vaRecord = VirtualAccount::where('pendaftar_id', $tagihan->pendaftar_id)
                    ->where('bank_id', $validated['bank_id'])
                    ->first();
                $nomorVa = $vaRecord?->nomor_va;
            }

            $pembayaran = Pembayaran::create([
                'tagihan_id' => $tagihan->id,
                'pendaftar_id' => $tagihan->pendaftar_id,
                'bank_id' => $validated['payment_method'] === 'TRANSFER' ? $validated['bank_id'] : null,
                'nomor_va' => $nomorVa,
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'status' => 'DITERIMA',
                'catatan' => $validated['catatan'] ?? ($validated['payment_method'] === 'SAMAHA' ? 'Potongan Samaha' : ($validated['payment_method'] === 'TRANSFER' ? 'Pembayaran VA Manual' : 'Pembayaran Tunai')),
                'verified_by' => auth()->id(),
                'created_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            // Recalculate tagihan status
            $this->recalculateTagihanStatus($tagihan);

            activity()
                ->useLog('Keuangan')
                ->event('created')
                ->performedOn($pembayaran)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'tagihan_id' => $tagihan->id,
                    'amount' => $validated['amount'],
                    'method' => $validated['payment_method'],
                ])
                ->log("Mencatat pembayaran {$validated['payment_method']} sebesar Rp ".number_format($validated['amount'], 0, ',', '.')." untuk invoice {$tagihan->nomor_invoice}");
        });

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function editPayment(Request $request, Pembayaran $pembayaran)
    {
        if (! $pembayaran->tagihan?->pendaftar?->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'catatan' => 'nullable|string|max:500',
        ]);

        $tagihan = $pembayaran->tagihan;
        if ($tagihan && $pembayaran->status === 'DITERIMA') {
            $otherPaid = (float) $tagihan->pembayarans()
                ->where('id', '!=', $pembayaran->id)
                ->where('status', 'DITERIMA')
                ->sum('amount');

            $maxAllowed = (float) $tagihan->total_amount - $otherPaid;

            if ((float) $validated['amount'] > $maxAllowed) {
                return back()->withErrors([
                    'amount' => 'Nominal pembayaran (Rp '.number_format((float) $validated['amount'], 0, ',', '.').') melebihi sisa tagihan (Rp '.number_format(max(0, $maxAllowed), 0, ',', '.').').',
                ]);
            }
        }

        DB::transaction(function () use ($pembayaran, $validated, $request) {
            $pembayaran->update([
                'amount' => $validated['amount'],
                'catatan' => $validated['catatan'] ?? $pembayaran->catatan,
            ]);

            if ($pembayaran->tagihan) {
                $this->recalculateTagihanStatus($pembayaran->tagihan);
            }

            activity()
                ->useLog('Keuangan')
                ->event('updated')
                ->performedOn($pembayaran)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Memperbarui nominal pembayaran menjadi Rp '.number_format($validated['amount'], 0, ',', '.'));
        });

        return back()->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    public function verifyPayment(Request $request, Pembayaran $pembayaran)
    {
        if (! $pembayaran->tagihan?->pendaftar?->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $validated = $request->validate([
            'action' => 'required|in:terima,tolak,kembalikan',
            'alasan_penolakan' => 'required_if:action,tolak|nullable|string|max:500',
            'amount_verified' => 'nullable|numeric|min:1',
            'catatan' => 'nullable|string|max:500',
        ], [
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika pembayaran ditolak.',
            'amount_verified.min' => 'Nominal pembayaran minimal Rp 1.',
        ]);

        $tagihan = $pembayaran->tagihan;

        $newAmount = isset($validated['amount_verified']) && (float) $validated['amount_verified'] > 0
            ? (float) $validated['amount_verified']
            : (float) $pembayaran->amount;

        if ($newAmount <= 0) {
            return back()->withErrors(['amount_verified' => 'Nominal pembayaran harus lebih besar dari Rp 0.']);
        }

        if ($validated['action'] === 'terima' && $tagihan) {
            $otherPaid = (float) $tagihan->pembayarans()
                ->where('id', '!=', $pembayaran->id)
                ->where('status', 'DITERIMA')
                ->sum('amount');

            $maxAllowed = (float) $tagihan->total_amount - $otherPaid;

            if ($newAmount > $maxAllowed) {
                return back()->withErrors([
                    'amount_verified' => 'Nominal pembayaran (Rp '.number_format($newAmount, 0, ',', '.').') melebihi sisa tagihan (Rp '.number_format(max(0, $maxAllowed), 0, ',', '.').').',
                ]);
            }
        }

        DB::transaction(function () use ($pembayaran, $validated, $request) {
            $tagihan = $pembayaran->tagihan;

            $newAmount = isset($validated['amount_verified']) && $validated['amount_verified'] > 0
                ? $validated['amount_verified']
                : $pembayaran->amount;

            if ($validated['action'] === 'terima') {
                $pembayaran->update([
                    'status' => 'DITERIMA',
                    'amount' => $newAmount,
                    'catatan' => $validated['catatan'] ?? 'Pembayaran diterima',
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);
            } elseif ($validated['action'] === 'tolak') {
                $pembayaran->update([
                    'status' => 'DITOLAK',
                    'amount' => $newAmount,
                    'catatan' => $validated['alasan_penolakan'] ?? ($validated['catatan'] ?? 'Pembayaran ditolak'),
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);
            } else {
                $pembayaran->update([
                    'status' => 'MENUNGGU_VERIFIKASI',
                    'amount' => $newAmount,
                    'catatan' => $validated['catatan'] ?? ($validated['alasan_penolakan'] ?? 'Dikembalikan ke status menunggu verifikasi'),
                    'verified_at' => null,
                    'verified_by' => null,
                ]);
            }

            if ($tagihan) {
                $this->recalculateTagihanStatus($tagihan);
            }

            activity()
                ->useLog('Keuangan')
                ->event('verified')
                ->performedOn($pembayaran)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'action' => $validated['action'],
                ])
                ->log("Memverifikasi/mengubah status pembayaran ({$validated['action']}) untuk invoice {$tagihan?->nomor_invoice}");
        });

        return back()->with('success', 'Status pembayaran berhasil diperbarui.');
    }

    public function deletePayment(Pembayaran $pembayaran)
    {
        if (! $pembayaran->tagihan?->pendaftar?->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        if (empty($pembayaran->created_by)) {
            return back()->withErrors(['message' => 'Pembayaran yang dilakukan oleh pendaftar tidak dapat dihapus.']);
        }

        DB::transaction(function () use ($pembayaran) {
            $tagihan = $pembayaran->tagihan;
            $pembayaran->delete();

            if ($tagihan) {
                $this->recalculateTagihanStatus($tagihan);
            }

            activity()
                ->useLog('Keuangan')
                ->event('deleted')
                ->performedOn($pembayaran)
                ->log('Menghapus pencatatan pembayaran Rp '.number_format((float) $pembayaran->amount, 0, ',', '.'));
        });

        return back()->with('success', 'Pembayaran berhasil dihapus.');
    }

    public function bulkDeletePayments(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:pembayarans,id',
        ]);

        $pembayarans = Pembayaran::whereIn('id', $validated['ids'])->get();

        $unauthorized = $pembayarans->filter(fn ($p) => empty($p->created_by) || ! $p->tagihan?->pendaftar?->isAccessibleBy(auth()->user()));
        if ($unauthorized->isNotEmpty()) {
            return back()->withErrors(['message' => 'Terdapat pembayaran pendaftar yang tidak memiliki hak akses atau tidak dapat dihapus.']);
        }

        DB::transaction(function () use ($pembayarans) {
            $tagihanIds = $pembayarans->pluck('tagihan_id')->unique();

            foreach ($pembayarans as $pembayaran) {
                $pembayaran->delete();
            }

            foreach ($tagihanIds as $tId) {
                $t = Tagihan::find($tId);
                if ($t) {
                    $this->recalculateTagihanStatus($t);
                }
            }

            activity()
                ->useLog('Keuangan')
                ->event('deleted')
                ->log("Menghapus {$pembayarans->count()} data pembayaran manual.");
        });

        return back()->with('success', "{$pembayarans->count()} pembayaran berhasil dihapus.");
    }

    private function recalculateTagihanStatus(Tagihan $tagihan): void
    {
        $totalPaid = (float) $tagihan->pembayarans()->where('status', 'DITERIMA')->sum('amount');
        $totalAmount = (float) $tagihan->total_amount;
        $pendaftar = $tagihan->pendaftar;

        if ($totalPaid >= $totalAmount && $totalAmount > 0) {
            $tagihan->update(['status' => 'LUNAS']);

            // Jika tagihan telah lunas, alihkan status pendaftar ke INTERVIEW
            if ($pendaftar && in_array($pendaftar->status?->value ?? $pendaftar->status, ['TAGIHAN', 'SUBMITTED', 'DRAFT'], true)) {
                $pendaftar->update(['status' => PendaftarStatus::Interview]);

                activity()
                    ->useLog('Pendaftar Tagihan')
                    ->event('updated')
                    ->performedOn($pendaftar)
                    ->log("Status pendaftar {$pendaftar->nama} otomatis dialihkan ke INTERVIEW karena tagihan ({$tagihan->nomor_invoice}) telah lunas.");
            }
        } elseif ($totalPaid > 0) {
            $tagihan->update(['status' => 'BELUM_LUNAS']);

            // Jika sebelumnya pendaftar berstatus INTERVIEW karena pelunasan lalu pembayaran berkurang/dibatalkan,
            // kembalikan ke TAGIHAN jika belum ada kelompok ujian / hasil ujian
            if ($pendaftar && ($pendaftar->status?->value ?? $pendaftar->status) === 'INTERVIEW') {
                if (! $pendaftar->hasil_ujian && ! $pendaftar->kelompokUjians()->exists()) {
                    $pendaftar->update(['status' => PendaftarStatus::Tagihan]);

                    activity()
                        ->useLog('Pendaftar Tagihan')
                        ->event('updated')
                        ->performedOn($pendaftar)
                        ->log("Status pendaftar {$pendaftar->nama} dikembalikan ke TAGIHAN karena tagihan ({$tagihan->nomor_invoice}) belum lunas.");
                }
            }
        } else {
            $tagihan->update(['status' => 'BELUM_BAYAR']);

            if ($pendaftar && ($pendaftar->status?->value ?? $pendaftar->status) === 'INTERVIEW') {
                if (! $pendaftar->hasil_ujian && ! $pendaftar->kelompokUjians()->exists()) {
                    $pendaftar->update(['status' => PendaftarStatus::Tagihan]);

                    activity()
                        ->useLog('Pendaftar Tagihan')
                        ->event('updated')
                        ->performedOn($pendaftar)
                        ->log("Status pendaftar {$pendaftar->nama} dikembalikan ke TAGIHAN karena tagihan ({$tagihan->nomor_invoice}) belum dibayar.");
                }
            }
        }
    }

    public function resetPassword(Request $request, Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

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
                ->useLog('Pendaftar Tagihan')
                ->event('updated')
                ->performedOn($pendaftar)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log("Mereset kata sandi pendaftar tagihan: {$pendaftar->nama} ({$pendaftar->nik})");
        });

        return back()->with('success', "Kata sandi pendaftar {$pendaftar->nama} berhasil diperbarui.");
    }

    public function destroy(Request $request, Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $oldData = $pendaftar->toArray();

        DB::transaction(function () use ($pendaftar, $request, $oldData) {
            $pendaftar->delete();

            activity()
                ->useLog('Pendaftar Tagihan')
                ->event('deleted')
                ->performedOn($pendaftar)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                ])
                ->log("Menghapus data pendaftar tagihan: {$oldData['nama']}");
        });

        return back()->with('success', 'Data pendaftar berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string',
        ]);

        $pendaftars = Pendaftar::accessibleBy(auth()->user())->whereIn('id', $validated['ids'])->get();

        if ($pendaftars->isEmpty()) {
            return back()->with('error', 'Tidak ada data pendaftar yang memiliki hak akses untuk dihapus.');
        }

        $count = $pendaftars->count();
        $idsToDelete = $pendaftars->pluck('id')->toArray();

        DB::transaction(function () use ($idsToDelete, $request, $count) {
            Pendaftar::whereIn('id', $idsToDelete)->delete();

            activity()
                ->useLog('Pendaftar Tagihan')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'count' => $count,
                    'ids' => $idsToDelete,
                ])
                ->log("Menghapus massal {$count} data pendaftar tagihan.");
        });

        return back()->with('success', "{$count} data pendaftar berhasil dihapus.");
    }

    public function export(Request $request): BinaryFileResponse
    {
        $ids = $request->query('ids');
        $selectedJenjangId = $request->query('jenjang_id');
        $search = $request->query('search');
        $cabangId = $request->query('cabang_id');
        $periodeId = $request->query('periode_id');
        $gelombangId = $request->query('gelombang_id');
        $gender = $request->query('gender');
        $statusPembuatan = $request->query('status_pembuatan_tagihan');
        $statusTagihan = $request->query('status_tagihan');
        $statusPembayaran = $request->query('status_pembayaran');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $tahunAkademikId = $activeTahunAkademik?->id;

        $fileName = 'Data_Pendaftar_Tagihan_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new TagihanPendaftarExport(
                $ids,
                $selectedJenjangId,
                $search,
                $cabangId,
                $periodeId,
                $gelombangId,
                $gender,
                $statusPembuatan,
                $statusTagihan,
                $statusPembayaran,
                $startDate,
                $endDate,
                $tahunAkademikId
            ),
            $fileName
        );
    }
}
