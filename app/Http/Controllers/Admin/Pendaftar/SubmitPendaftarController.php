<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Enums\StatusPeriode;
use App\Exports\SubmitPendaftarExport;
use App\Http\Controllers\Controller;
use App\Models\Master\Cabang;
use App\Models\Master\Dokumen;
use App\Models\Master\Jenjang;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftar\PendaftarDokumen;
use App\Models\Pendaftaran\Gelombang;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubmitPendaftarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pendaftar.view', only: ['index']),
            new Middleware('permission:pendaftar.edit', only: ['verify', 'bulkVerify', 'resetPassword']),
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

            return Inertia::render('Admin/Pendaftar/Submit/Index', [
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

        // Count per jenjang for SUBMITTED status (scoped to active academic year and selected wave)
        $jenjangCounts = [];
        foreach ($jenjangs as $j) {
            $jenjangCounts[$j->id] = Pendaftar::accessibleBy()
                ->where('status', 'SUBMITTED')
                ->where('jenjang_id', $j->id)
                ->whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
                ->when($gelombangId, fn ($q) => $q->where('gelombang_id', $gelombangId))
                ->count();
        }

        // Main Query (strictly scoped to active academic year)
        $query = Pendaftar::accessibleBy()
            ->where('status', 'SUBMITTED')
            ->whereHas('periode', fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
            ->with(['cabang', 'jenjang', 'periode.tahunAkademik', 'gelombang', 'dokumens.dokumen', 'virtualAccounts.bank']);

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

        if ($tipePendaftaran) {
            $query->where('tipe_pendaftaran', $tipePendaftaran);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $pendaftars = $query->latest('submitted_at')->latest('created_at')->paginate($limit)->withQueryString();

        // Master options for filters
        $cabangs = Cabang::orderBy('name')->get();
        $masterDokumens = Dokumen::with('jenjangs:id,name,code,singkatan')->get();

        return Inertia::render('Admin/Pendaftar/Submit/Index', [
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

    public function verify(Request $request, Pendaftar $pendaftar)
    {
        $validated = $request->validate([
            'action' => 'required|in:terima,tolak',
            'catatan_personal' => 'nullable|string|max:1000',
            'catatan_parent' => 'nullable|string|max:1000',
            'catatan_address' => 'nullable|string|max:1000',
            'catatan_education' => 'nullable|string|max:1000',
            'dokumen_catatan' => 'nullable|array',
            'dokumen_catatan.*' => 'nullable|string|max:1000',
        ], [
            'action.required' => 'Pilihan verifikasi wajib ditentukan.',
            'action.in' => 'Pilihan verifikasi harus terima atau tolak.',
        ]);

        if ($validated['action'] === 'tolak') {
            $hasAnyNote = ! empty($validated['catatan_personal'])
                || ! empty($validated['catatan_parent'])
                || ! empty($validated['catatan_address'])
                || ! empty($validated['catatan_education'])
                || collect($validated['dokumen_catatan'] ?? [])->filter()->isNotEmpty();

            if (! $hasAnyNote) {
                return back()->withErrors(['catatan_personal' => 'Harap isi setidaknya satu catatan perbaikan pada data atau dokumen.']);
            }
        }

        DB::transaction(function () use ($pendaftar, $validated, $request) {
            $personal = $pendaftar->personal_data ?? [];
            $parent = $pendaftar->parent_data ?? [];
            $address = $pendaftar->address_data ?? [];
            $education = $pendaftar->education_data ?? [];

            if ($validated['action'] === 'terima') {
                $pendaftar->update([
                    'status' => 'TAGIHAN',
                ]);

                // Clear or mark approved all documents
                $pendaftar->dokumens()->update([
                    'status' => 'APPROVED',
                    'catatan' => null,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

                activity()
                    ->useLog('Pendaftar Submit')
                    ->event('verified')
                    ->performedOn($pendaftar)
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'action' => 'terima',
                    ])
                    ->log("Menerima verifikasi pendaftaran calon santri: {$pendaftar->nama} (Status dialihkan ke TAGIHAN)");
            } else {
                $personal['catatan_revisi_at'] = now()->toISOString();
                $personal['catatan_personal'] = $validated['catatan_personal'] ?? null;

                $parent['catatan_parent'] = $validated['catatan_parent'] ?? null;
                $address['catatan_address'] = $validated['catatan_address'] ?? null;
                $education['catatan_education'] = $validated['catatan_education'] ?? null;

                $pendaftar->update([
                    'status' => 'DRAFT',
                    'personal_data' => $personal,
                    'parent_data' => $parent,
                    'address_data' => $address,
                    'education_data' => $education,
                ]);

                // Update document notes & status if any
                if (! empty($validated['dokumen_catatan'])) {
                    foreach ($validated['dokumen_catatan'] as $dokumenId => $catatanDok) {
                        if (! empty($catatanDok)) {
                            PendaftarDokumen::where('pendaftar_id', $pendaftar->id)
                                ->where('dokumen_id', $dokumenId)
                                ->update([
                                    'catatan' => $catatanDok,
                                    'status' => 'REJECTED',
                                    'verified_by' => auth()->id(),
                                    'verified_at' => now(),
                                ]);
                        }
                    }
                }

                activity()
                    ->useLog('Pendaftar Submit')
                    ->event('rejected')
                    ->performedOn($pendaftar)
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'action' => 'tolak',
                    ])
                    ->log("Menolak berkas pendaftaran calon santri: {$pendaftar->nama} (Status dikembalikan ke DRAFT dengan catatan perbaikan)");
            }
        });

        $message = $validated['action'] === 'terima'
            ? "Pendaftaran calon santri {$pendaftar->nama} berhasil diverifikasi dan dialihkan ke tahap Tagihan."
            : "Pendaftaran calon santri {$pendaftar->nama} ditolak dan dikembalikan ke tahap Draft untuk revisi.";

        return back()->with('success', $message);
    }

    public function bulkVerify(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string',
            'action' => 'required|in:terima',
        ]);

        $count = count($validated['ids']);

        DB::transaction(function () use ($validated, $request, $count) {
            $pendaftars = Pendaftar::whereIn('id', $validated['ids'])->get();

            foreach ($pendaftars as $p) {
                $p->update([
                    'status' => 'TAGIHAN',
                ]);
            }

            activity()
                ->useLog('Pendaftar Submit')
                ->event('bulk_verified')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'action' => 'terima',
                    'count' => $count,
                    'ids' => $validated['ids'],
                ])
                ->log("Melakukan verifikasi terima massal terhadap {$count} data pendaftar submit.");
        });

        $message = "Sebanyak {$count} calon santri berhasil diverifikasi dan dialihkan ke tahap Tagihan.";

        return back()->with('success', $message);
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
                ->useLog('Pendaftar Submit')
                ->event('updated')
                ->performedOn($pendaftar)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log("Mereset kata sandi pendaftar submit: {$pendaftar->nama} ({$pendaftar->nik})");
        });

        return back()->with('success', "Kata sandi pendaftar {$pendaftar->nama} berhasil diperbarui.");
    }

    public function destroy(Request $request, Pendaftar $pendaftar)
    {
        $oldData = $pendaftar->toArray();

        DB::transaction(function () use ($pendaftar, $request, $oldData) {
            $pendaftar->delete();

            activity()
                ->useLog('Pendaftar Submit')
                ->event('deleted')
                ->performedOn($pendaftar)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                ])
                ->log("Menghapus data pendaftar submit: {$oldData['nama']}");
        });

        return back()->with('success', 'Data pendaftar submit berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string',
        ]);

        $count = count($validated['ids']);

        DB::transaction(function () use ($validated, $request) {
            $pendaftars = Pendaftar::whereIn('id', $validated['ids'])->get();

            Pendaftar::whereIn('id', $validated['ids'])->delete();

            activity()
                ->useLog('Pendaftar Submit')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'count' => count($validated['ids']),
                    'ids' => $validated['ids'],
                ])
                ->log("Menghapus massal {$pendaftars->count()} data pendaftar submit.");
        });

        return back()->with('success', "{$count} data pendaftar submit berhasil dihapus.");
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
        $tipePendaftaran = $request->query('tipe_pendaftaran');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
        $tahunAkademikId = $activeTahunAkademik?->id;

        $fileName = 'Data_Pendaftar_Submit_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new SubmitPendaftarExport(
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
