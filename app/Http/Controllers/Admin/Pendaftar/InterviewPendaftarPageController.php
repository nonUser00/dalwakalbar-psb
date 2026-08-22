<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Enums\PendaftarStatus;
use App\Enums\StatusKelompokUjian;
use App\Enums\StatusPeriode;
use App\Exports\InterviewPendaftarExport;
use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Ujian\KelompokUjian;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterviewPendaftarPageController extends Controller implements HasMiddleware
{
    /**
     * Define controller middleware permissions.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pendaftar.view', only: ['index', 'create', 'export']),
            new Middleware('permission:pendaftar.edit', only: ['schedule', 'removeSchedule', 'resetPassword']),
            new Middleware('permission:pendaftar.delete', only: ['destroy', 'bulkDestroy']),
            new Middleware('permission:pendaftar.export', only: ['export']),
        ];
    }

    /**
     * Display the Pendaftar Interview list page.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 10);
        $cabangId = $request->query('cabang_id');
        $hasExplicitGelombang = $request->has('gelombang_id');
        $gelombangId = $request->query('gelombang_id');
        $gender = $request->query('gender');
        $statusPembuatan = $request->query('status_pembuatan_interview');
        $pengujiId = $request->query('penguji_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // All Jenjangs ordered
        $jenjangs = Jenjang::orderBy('created_at', 'asc')->get();
        $cabangs = Cabang::where('is_active', true)->orderBy('name')->get();

        // Selected active jenjang (empty by default to show all data)
        $selectedJenjangId = $request->query('jenjang_id');

        // Get Active Academic Year
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $hasActiveTahunAkademik = $activeTahunAkademik !== null;

        if (! $hasActiveTahunAkademik) {
            $badgeCounts = [];
            foreach ($jenjangs as $j) {
                $badgeCounts[$j->id] = 0;
            }

            return Inertia::render('Admin/Pendaftar/SetInterview/Index', [
                'pendaftars' => Pendaftar::whereRaw('1 = 0')->paginate($limit)->withQueryString(),
                'jenjangs' => $jenjangs,
                'jenjangCounts' => $badgeCounts,
                'selectedJenjangId' => (string) ($selectedJenjangId ?? ''),
                'cabangs' => $cabangs,
                'activeTahunAkademik' => null,
                'hasActiveTahunAkademik' => false,
                'gelombangs' => [],
                'pengujis' => User::select('id', 'name', 'email')->where('is_active', true)->orderBy('name')->get(),
                'koordinator' => User::select('id', 'name', 'email')->where('is_active', true)->orderBy('name')->get(),
                'pengawas' => User::select('id', 'name', 'email')->where('is_active', true)->orderBy('name')->get(),
                'kelompokUjians' => [],
                'filters' => [
                    'search' => (string) ($search ?? ''),
                    'limit' => $limit,
                    'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                    'cabang_id' => (string) ($cabangId ?? ''),
                    'gelombang_id' => (string) ($gelombangId ?? ''),
                    'gender' => (string) ($gender ?? ''),
                    'status_pembuatan_interview' => (string) ($statusPembuatan ?? ''),
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
                $gelombangId = $matchingWave['id'];
            }
        }

        // Count per jenjang for INTERVIEW status who have NOT been scheduled yet (scoped to active academic year and selected wave)
        $jenjangCounts = [];
        foreach ($jenjangs as $j) {
            $jenjangCounts[$j->id] = Pendaftar::where('status', PendaftarStatus::Interview)
                ->where('jenjang_id', $j->id)
                ->whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
                ->when($gelombangId, fn ($q) => $q->where('gelombang_id', $gelombangId))
                ->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                    })->doesntHave('kelompokUjians')
                        ->orWhere(function ($sq) {
                            $sq->where('is_interview_ulang', true)->whereDoesntHave('kelompokUjians', function ($kq) {
                                $kq->whereColumn('kelompok_ujian_pendaftar.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                        });
                })
                ->count();
        }

        // Base candidate query (strictly status INTERVIEW, not yet scheduled, and in active academic year)
        $query = Pendaftar::query()
            ->where('status', PendaftarStatus::Interview)
            ->whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                })->doesntHave('kelompokUjians')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)->whereDoesntHave('kelompokUjians', function ($kq) {
                            $kq->whereColumn('kelompok_ujian_pendaftar.created_at', '>=', 'pendaftars.interview_ulang_at');
                        });
                    });
            })
            ->with([
                'cabang',
                'jenjang',
                'periode.tahunAkademik',
                'gelombang',
                'dokumens.dokumen',
                'virtualAccounts.bank',
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
                    ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nomor_hp', 'like', "%{$search}%");
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
            $query->has('kelompokUjians');
        } elseif ($statusPembuatan === 'belum') {
            $query->doesntHave('kelompokUjians');
        }

        if ($pengujiId) {
            $query->whereHas('kelompokUjians', function ($q) use ($pengujiId) {
                $q->whereHas('pengujis', function ($q2) use ($pengujiId) {
                    $q2->where('users.id', $pengujiId);
                });
            });
        }

        if ($startDate) {
            $query->whereDate('submitted_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('submitted_at', '<=', $endDate);
        }

        $pendaftars = $query->latest('submitted_at')->latest('created_at')->paginate($limit)->withQueryString();

        $pengujis = User::select('id', 'name', 'email')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $koordinator = User::select('id', 'name', 'email')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $kelompokUjians = KelompokUjian::with(['pengujis', 'koordinator'])
            ->orderBy('tanggal_ujian', 'asc')
            ->latest('tanggal_ujian')
            ->get();

        return Inertia::render('Admin/Pendaftar/SetInterview/Index', [
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
            'pengujis' => $pengujis,
            'koordinator' => $koordinator,
            'pengawas' => $koordinator,
            'kelompokUjians' => $kelompokUjians,
            'filters' => [
                'search' => (string) ($search ?? ''),
                'limit' => $limit,
                'jenjang_id' => (string) ($selectedJenjangId ?? ''),
                'cabang_id' => (string) ($cabangId ?? ''),
                'gelombang_id' => (string) ($gelombangId ?? ''),
                'gender' => (string) ($gender ?? ''),
                'status_pembuatan_interview' => (string) ($statusPembuatan ?? ''),
                'penguji_id' => (string) ($pengujiId ?? ''),
                'start_date' => (string) ($startDate ?? ''),
                'end_date' => (string) ($endDate ?? ''),
            ],
        ]);
    }

    /**
     * Display the Create / Set Interview Schedule page.
     */
    public function create(Request $request)
    {
        $idsParam = $request->query('ids') ?: $request->query('pendaftar_id');
        $ids = [];
        if ($idsParam) {
            $ids = is_array($idsParam) ? $idsParam : explode(',', $idsParam);
        }

        $targetPendaftars = Pendaftar::whereIn('id', $ids)
            ->with(['cabang', 'jenjang', 'periode', 'gelombang', 'dokumens.dokumen', 'kelompokUjians.pengujis', 'kelompokUjians.koordinator'])
            ->get();

        $jenjangs = Jenjang::orderBy('created_at', 'asc')->get();

        // Candidates eligible for interview (across all jenjangs) who haven't been scheduled in the current cycle
        $availablePendaftars = Pendaftar::query()
            ->where('status', PendaftarStatus::Interview)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                })->doesntHave('kelompokUjians')
                    ->orWhere(function ($sq) {
                        $sq->where('is_interview_ulang', true)->whereDoesntHave('kelompokUjians', function ($kq) {
                            $kq->whereColumn('kelompok_ujian_pendaftar.created_at', '>=', 'pendaftars.interview_ulang_at');
                        });
                    });
            })
            ->with(['cabang', 'jenjang', 'periode', 'gelombang', 'dokumens.dokumen'])
            ->latest('submitted_at')
            ->get();

        $cabangs = Cabang::where('is_active', true)->orderBy('name')->get();

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

        $kelompokUjians = KelompokUjian::with(['pengujis', 'koordinator'])
            ->withCount('pendaftars')
            ->whereDate('tanggal_ujian', '>=', now()->addDay()->toDateString())
            ->doesntHave('penilaians')
            ->where('status', StatusKelompokUjian::Scheduled)
            ->latest('tanggal_ujian')
            ->get();

        return Inertia::render('Admin/Pendaftar/SetInterview/Create', [
            'targetPendaftars' => $targetPendaftars,
            'selectedIds' => $ids,
            'jenjangs' => $jenjangs,
            'cabangs' => $cabangs,
            'pengujis' => $pengujis,
            'koordinator' => $koordinator,
            'pengawas' => $koordinator,
            'kelompokUjians' => $kelompokUjians,
            'availablePendaftars' => $availablePendaftars,
        ]);
    }

    /**
     * Schedule single or bulk applicants into a Kelompok Ujian.
     */
    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'pendaftar_ids' => 'required|array|min:1',
            'pendaftar_ids.*' => 'exists:pendaftars,id',
            'mode' => 'required|string|in:create_new,existing',
            // Fields for new schedule
            'nama_kelompok' => 'required_if:mode,create_new|nullable|string|max:255',
            'interview_penguji_ids' => 'required_if:mode,create_new|nullable|array|min:1|max:1',
            'interview_penguji_ids.*' => 'exists:users,id',
            'tes_membaca_penguji_ids' => 'required_if:mode,create_new|nullable|array|min:1|max:1',
            'tes_membaca_penguji_ids.*' => 'exists:users,id',
            'tes_menulis_penguji_ids' => 'required_if:mode,create_new|nullable|array|min:1|max:1',
            'tes_menulis_penguji_ids.*' => 'exists:users,id',
            'tes_hafalan_penguji_ids' => 'required_if:mode,create_new|nullable|array|min:1|max:1',
            'tes_hafalan_penguji_ids.*' => 'exists:users,id',
            'koordinator_ids' => 'required_if:mode,create_new|nullable|array|min:1|max:1',
            'koordinator_ids.*' => 'exists:users,id',
            'pengawas_ids' => 'nullable|array|max:1',
            'pengawas_ids.*' => 'exists:users,id',
            'tanggal_ujian' => 'required_if:mode,create_new|nullable|date',
            'waktu_mulai' => 'required_if:mode,create_new|nullable',
            'waktu_selesai' => 'required_if:mode,create_new|nullable',
            'lokasi' => 'required_if:mode,create_new|nullable|string|max:255',
            // Field for existing schedule
            'kelompok_ujian_id' => 'required_if:mode,existing|nullable|exists:kelompok_ujians,id',
        ], [
            'interview_penguji_ids.required_if' => 'Wajib memilih 1 orang pewawancara untuk sesi Interview.',
            'interview_penguji_ids.min' => 'Wajib memilih 1 orang pewawancara untuk sesi Interview.',
            'interview_penguji_ids.max' => 'Pewawancara (Interview) hanya boleh dipilih 1 orang.',
            'tes_membaca_penguji_ids.required_if' => 'Wajib memilih 1 orang penguji untuk Tes Membaca.',
            'tes_membaca_penguji_ids.min' => 'Wajib memilih 1 orang penguji untuk Tes Membaca.',
            'tes_membaca_penguji_ids.max' => 'Penguji Tes Membaca hanya boleh dipilih 1 orang.',
            'tes_menulis_penguji_ids.required_if' => 'Wajib memilih 1 orang penguji untuk Tes Menulis.',
            'tes_menulis_penguji_ids.min' => 'Wajib memilih 1 orang penguji untuk Tes Menulis.',
            'tes_menulis_penguji_ids.max' => 'Penguji Tes Menulis hanya boleh dipilih 1 orang.',
            'tes_hafalan_penguji_ids.required_if' => 'Wajib memilih 1 orang penguji untuk Tes Hafalan.',
            'tes_hafalan_penguji_ids.min' => 'Wajib memilih 1 orang penguji untuk Tes Hafalan.',
            'tes_hafalan_penguji_ids.max' => 'Penguji Tes Hafalan hanya boleh dipilih 1 orang.',
            'koordinator_ids.required_if' => 'Wajib memilih 1 orang Koordinator PSB.',
            'koordinator_ids.min' => 'Wajib memilih 1 orang Koordinator PSB.',
            'koordinator_ids.max' => 'Koordinator PSB hanya boleh dipilih 1 orang.',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $kelompok = null;

            if ($validated['mode'] === 'create_new') {
                $kelompok = KelompokUjian::create([
                    'nama_kelompok' => $validated['nama_kelompok'],
                    'tanggal_ujian' => $validated['tanggal_ujian'],
                    'waktu_mulai' => $validated['waktu_mulai'],
                    'waktu_selesai' => $validated['waktu_selesai'],
                    'lokasi' => $validated['lokasi'],
                    'status' => 'scheduled',
                ]);

                if (! empty($validated['interview_penguji_ids'])) {
                    foreach ($validated['interview_penguji_ids'] as $uid) {
                        $kelompok->pengujis()->attach($uid, ['peran' => 'interview']);
                    }
                }

                if (! empty($validated['tes_membaca_penguji_ids'])) {
                    foreach ($validated['tes_membaca_penguji_ids'] as $uid) {
                        $kelompok->pengujis()->attach($uid, ['peran' => 'tes_membaca']);
                    }
                }

                if (! empty($validated['tes_menulis_penguji_ids'])) {
                    foreach ($validated['tes_menulis_penguji_ids'] as $uid) {
                        $kelompok->pengujis()->attach($uid, ['peran' => 'tes_menulis']);
                    }
                }

                if (! empty($validated['tes_hafalan_penguji_ids'])) {
                    foreach ($validated['tes_hafalan_penguji_ids'] as $uid) {
                        $kelompok->pengujis()->attach($uid, ['peran' => 'tes_hafalan']);
                    }
                }

                $koordinatorIds = $validated['koordinator_ids'] ?? $validated['pengawas_ids'] ?? [];
                if (! empty($koordinatorIds)) {
                    $kelompok->koordinator()->sync($koordinatorIds);
                }
            } else {
                $kelompok = KelompokUjian::where('id', $validated['kelompok_ujian_id'])
                    ->whereDate('tanggal_ujian', '>=', now()->addDay()->toDateString())
                    ->doesntHave('penilaians')
                    ->where('status', StatusKelompokUjian::Scheduled)
                    ->first();

                if (! $kelompok) {
                    throw ValidationException::withMessages([
                        'kelompok_ujian_id' => 'Kelompok ujian tidak valid. Hanya kelompok dengan jadwal minimal H-1 sebelum pelaksanaan dan belum dinilai yang dapat digabungkan.',
                    ]);
                }
            }

            // Sync/attach applicants to the kelompok ujian without detaching others
            $kelompok->pendaftars()->syncWithoutDetaching($validated['pendaftar_ids']);

            // Update candidate status to INTERVIEW
            Pendaftar::whereIn('id', $validated['pendaftar_ids'])->update([
                'status' => 'INTERVIEW',
            ]);

            activity()
                ->useLog('Pendaftar Interview')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'kelompok_id' => $kelompok->id,
                    'kelompok_name' => $kelompok->nama_kelompok,
                    'total_assigned' => count($validated['pendaftar_ids']),
                ])
                ->log('Menjadwalkan '.count($validated['pendaftar_ids'])." calon santri ke kelompok ujian '{$kelompok->nama_kelompok}'");

            if ($request->input('redirect_to_index')) {
                return redirect()->route('admin.pendaftar.set_interview.index', [
                    'jenjang_id' => $request->input('jenjang_id'),
                ])->with('success', 'Jadwal interview berhasil ditetapkan untuk '.count($validated['pendaftar_ids']).' calon santri.');
            }

            return back()->with('success', 'Jadwal interview berhasil ditetapkan untuk '.count($validated['pendaftar_ids']).' calon santri.');
        });
    }

    /**
     * Remove applicant from a scheduled Kelompok Ujian.
     */
    public function removeSchedule(Request $request, Pendaftar $pendaftar)
    {
        $kelompokUjianId = $request->input('kelompok_ujian_id');

        return DB::transaction(function () use ($pendaftar, $kelompokUjianId, $request) {
            if ($kelompokUjianId) {
                $pendaftar->kelompokUjians()->detach($kelompokUjianId);
            } else {
                $pendaftar->kelompokUjians()->detach();
            }

            // If no other kelompok ujian attached, keep status INTERVIEW (ready for another schedule)
            if ($pendaftar->kelompokUjians()->count() === 0) {
                $pendaftar->update([
                    'status' => PendaftarStatus::Interview,
                ]);
            }

            activity()
                ->useLog('Pendaftar Interview')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'pendaftar_id' => $pendaftar->id,
                    'pendaftar_nama' => $pendaftar->nama,
                ])
                ->log("Membatalkan jadwal interview calon santri {$pendaftar->nama}");

            return back()->with('success', "Jadwal interview untuk calon santri {$pendaftar->nama} berhasil dibatalkan.");
        });
    }

    /**
     * Reset password for an applicant.
     */
    public function resetPassword(Request $request, Pendaftar $pendaftar)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $pendaftar->update([
            'password' => Hash::make($validated['password']),
        ]);

        activity()
            ->useLog('Pendaftar Interview')
            ->event('updated')
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'pendaftar_id' => $pendaftar->id,
            ])
            ->log("Mereset kata sandi akun calon santri {$pendaftar->nama}");

        return back()->with('success', "Kata sandi untuk {$pendaftar->nama} berhasil diperbarui.");
    }

    /**
     * Soft delete an applicant.
     */
    public function destroy(Request $request, Pendaftar $pendaftar)
    {
        $nama = $pendaftar->nama;

        DB::transaction(function () use ($pendaftar, $request, $nama) {
            $pendaftar->delete();

            activity()
                ->useLog('Pendaftar Interview')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'pendaftar_id' => $pendaftar->id,
                ])
                ->log("Menghapus data calon santri {$nama}");
        });

        return back()->with('success', "Data calon santri {$nama} berhasil dihapus.");
    }

    /**
     * Bulk delete applicants.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:pendaftars,id',
        ]);

        $count = count($validated['ids']);

        DB::transaction(function () use ($validated, $request, $count) {
            Pendaftar::whereIn('id', $validated['ids'])->delete();

            activity()
                ->useLog('Pendaftar Interview')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'total_deleted' => $count,
                ])
                ->log("Menghapus massal {$count} data calon santri interview");
        });

        return back()->with('success', "Berhasil menghapus {$count} data calon santri.");
    }

    /**
     * Export applicants to Excel (.xlsx) format.
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
        $statusPembuatan = $request->query('status_pembuatan_interview');
        $pengujiId = $request->query('penguji_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $tahunAkademikId = $activeTahunAkademik?->id;

        $fileName = 'Data_Pendaftar_Set_Interview_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new InterviewPendaftarExport(
                $ids,
                $selectedJenjangId,
                $search,
                $cabangId,
                $periodeId,
                $gelombangId,
                $gender,
                $statusPembuatan,
                $pengujiId,
                $startDate,
                $endDate,
                $tahunAkademikId
            ),
            $fileName
        );
    }
}
