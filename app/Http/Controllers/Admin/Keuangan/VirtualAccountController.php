<?php

namespace App\Http\Controllers\Admin\Keuangan;

use App\Exports\VirtualAccountExport;
use App\Exports\VirtualAccountTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Keuangan\Bank;
use App\Models\Keuangan\VirtualAccount;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class VirtualAccountController extends Controller
{
    public function index(Request $request)
    {
        $banks = Bank::where('is_active', true)
            ->orderBy('kode_bank')
            ->orderBy('singkatan')
            ->orderBy('name')
            ->get();

        $cabangs = Cabang::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
        if (empty($cabangs)) {
            $cabangs = ['Kalimantan Barat', 'Kalimantan Timur'];
        }

        $jenjangs = Jenjang::where('is_active', true)->orderBy('code')->pluck('name')->toArray();
        if (empty($jenjangs)) {
            $jenjangs = ['Madrasah Tsanawiyah', 'Madrasah Aliyah', 'Strata 1 (Sarjana)', 'Pasca Sarjana (Magister)', 'Doktor (S3)'];
        }

        $allPendaftars = Pendaftar::with(['cabang', 'jenjang'])
            ->orderBy('nama')
            ->get()
            ->map(function ($p) {
                $cabang = $p->cabang?->name ?? $p->cabang?->singkatan ?? $p->personal_data['cabang_pendaftaran'] ?? null;
                $jenjang = $p->jenjang?->name ?? $p->jenjang?->code ?? $p->jenjang?->singkatan ?? $p->education_data['jenjang'] ?? null;

                return [
                    'id' => $p->id,
                    'nik' => $p->nik,
                    'name' => $p->nama,
                    'nomor_pendaftaran' => $p->nomor_pendaftaran,
                    'cabang' => $cabang,
                    'jenjang' => $jenjang,
                ];
            });

        $query = Pendaftar::with([
            'virtualAccounts' => function ($q) {
                $q->whereHas('bank', function ($b) {
                    $b->where('is_active', true);
                });
            },
            'virtualAccounts.bank',
            'cabang',
            'jenjang',
        ])->orderBy('nama');

        // Search Filter (Server-side)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('nik', 'like', '%'.$search.'%')
                    ->orWhere('nomor_pendaftaran', 'like', '%'.$search.'%')
                    ->orWhereHas('cabang', function ($c) use ($search) {
                        $c->where('name', 'like', '%'.$search.'%')
                            ->orWhere('singkatan', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('jenjang', function ($j) use ($search) {
                        $j->where('name', 'like', '%'.$search.'%')
                            ->orWhere('code', 'like', '%'.$search.'%')
                            ->orWhere('singkatan', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('virtualAccounts', function ($v) use ($search) {
                        $v->where('nomor_va', 'like', '%'.$search.'%');
                    });
            });
        }

        // Filter Cabang (Server-side)
        if ($request->filled('cabang')) {
            $cabangVal = $request->cabang;
            $query->where(function ($q) use ($cabangVal) {
                $q->whereHas('cabang', function ($c) use ($cabangVal) {
                    $c->where('name', $cabangVal)
                        ->orWhere('singkatan', $cabangVal);
                })->orWhere('personal_data->cabang_pendaftaran', 'like', '%'.$cabangVal.'%');
            });
        }

        // Filter Jenjang (Server-side)
        if ($request->filled('jenjang')) {
            $jenjangVal = $request->jenjang;
            $query->where(function ($q) use ($jenjangVal) {
                $q->whereHas('jenjang', function ($j) use ($jenjangVal) {
                    $j->where('name', $jenjangVal)
                        ->orWhere('code', $jenjangVal)
                        ->orWhere('singkatan', $jenjangVal);
                })->orWhere('education_data->jenjang', 'like', '%'.$jenjangVal.'%');
            });
        }

        // Filter Status VA (Server-side)
        if ($request->filled('status_va')) {
            $status = $request->status_va;
            if ($status === 'Sudah Ada VA') {
                $query->whereHas('virtualAccounts', function ($q) {
                    $q->whereNotNull('nomor_va')->where('nomor_va', '!=', '');
                });
            } elseif ($status === 'Belum Ada VA') {
                $query->whereDoesntHave('virtualAccounts', function ($q) {
                    $q->whereNotNull('nomor_va')->where('nomor_va', '!=', '');
                });
            } elseif ($status === 'Sudah Lengkap') {
                $activeBankCount = $banks->count();
                if ($activeBankCount > 0) {
                    $query->has('virtualAccounts', '>=', $activeBankCount);
                }
            }
        }

        $limit = $request->input('limit', 10);
        $paginatedPendaftars = $query->paginate($limit)->withQueryString();

        $paginatedPendaftars->getCollection()->transform(function ($pendaftar) use ($banks) {
            $cabang = $pendaftar->cabang?->name ?? $pendaftar->cabang?->singkatan ?? $pendaftar->personal_data['cabang_pendaftaran'] ?? '-';
            $jenjang = $pendaftar->jenjang?->name ?? $pendaftar->jenjang?->code ?? $pendaftar->jenjang?->singkatan ?? $pendaftar->education_data['jenjang'] ?? '-';

            $row = [
                'id' => $pendaftar->id,
                'nik' => $pendaftar->nik,
                'name' => $pendaftar->nama,
                'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                'cabang' => $cabang,
                'jenjang' => $jenjang,
                'va_numbers' => [],
                'va_ids' => [],
                'all_va_ids' => $pendaftar->virtualAccounts->pluck('id')->toArray(),
            ];

            $configuredCount = 0;
            foreach ($banks as $bank) {
                $va = $pendaftar->virtualAccounts->firstWhere('bank_id', $bank->id);
                $val = $va ? $va->nomor_va : null;
                $row['va_numbers'][$bank->id] = $val;
                $row['va_ids'][$bank->id] = $va ? $va->id : null;
                if (! empty($val)) {
                    $configuredCount++;
                }
            }

            $row['has_va'] = $configuredCount > 0;
            $row['is_complete'] = $configuredCount === count($banks);

            return $row;
        });

        return Inertia::render('Admin/Keuangan/VirtualAccount/Index', [
            'banks' => $banks,
            'pendaftars' => $paginatedPendaftars,
            'allPendaftars' => $allPendaftars,
            'cabangs' => $cabangs,
            'jenjangs' => $jenjangs,
            'filters' => $request->only(['search', 'cabang', 'jenjang', 'status_va', 'limit']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pendaftar_id' => 'required|exists:pendaftars,id',
            'vas' => 'required|array',
            'vas.*.bank_id' => ['required', Rule::exists('banks', 'id')->where('is_active', true)],
            'vas.*.nomor_va' => 'nullable|string|max:50',
        ]);

        $pendaftar = Pendaftar::findOrFail($validated['pendaftar_id']);

        DB::transaction(function () use ($validated, $pendaftar, $request) {
            foreach ($validated['vas'] as $item) {
                $nomorVa = trim($item['nomor_va'] ?? '');

                if ($nomorVa !== '') {
                    VirtualAccount::updateOrCreate(
                        [
                            'pendaftar_id' => $pendaftar->id,
                            'bank_id' => $item['bank_id'],
                        ],
                        [
                            'nomor_va' => $nomorVa,
                        ]
                    );
                } else {
                    VirtualAccount::where('pendaftar_id', $pendaftar->id)
                        ->where('bank_id', $item['bank_id'])
                        ->delete();
                }
            }

            activity()
                ->useLog('Virtual Account')
                ->event('saved')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'pendaftar_id' => $pendaftar->id])
                ->log("Mengatur Virtual Account untuk santri: {$pendaftar->nama}");
        });

        return redirect()->back()->with('success', 'Nomor Virtual Account berhasil disimpan.');
    }

    public function update(Request $request, string $id)
    {
        return $this->store($request);
    }

    public function destroy(Request $request, string $id)
    {
        if ($id === 'bulk') {
            $pendaftarIds = $request->input('pendaftar_ids', []);
            $vaIds = $request->input('ids', []);

            DB::transaction(function () use ($pendaftarIds, $vaIds, $request) {
                if (! empty($pendaftarIds)) {
                    VirtualAccount::whereIn('pendaftar_id', $pendaftarIds)->delete();
                } elseif (! empty($vaIds)) {
                    VirtualAccount::whereIn('id', $vaIds)->delete();
                }

                activity()
                    ->useLog('Virtual Account')
                    ->event('deleted')
                    ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'pendaftar_ids' => $pendaftarIds, 'va_ids' => $vaIds])
                    ->log('Menghapus massal Virtual Account');
            });

            return back()->with('success', 'Virtual Account terpilih berhasil dihapus.');
        }

        DB::transaction(function () use ($id, $request) {
            $deleted = VirtualAccount::where('pendaftar_id', $id)->delete();
            if (! $deleted) {
                VirtualAccount::where('id', $id)->delete();
            }

            activity()
                ->useLog('Virtual Account')
                ->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'target_id' => $id])
                ->log("Menghapus Virtual Account ID: {$id}");
        });

        return back()->with('success', 'Virtual Account berhasil dihapus.');
    }

    public function importPage()
    {
        $banks = Bank::where('is_active', true)->orderBy('kode_bank')->orderBy('singkatan')->orderBy('name')->get();

        $cabangs = Cabang::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
        if (empty($cabangs)) {
            $cabangs = ['Kalimantan Barat', 'Kalimantan Timur'];
        }

        $jenjangs = Jenjang::where('is_active', true)->orderBy('code')->pluck('name')->toArray();
        if (empty($jenjangs)) {
            $jenjangs = ['Madrasah Tsanawiyah', 'Madrasah Aliyah', 'Strata 1 (Sarjana)', 'Pasca Sarjana (Magister)', 'Doktor (S3)'];
        }

        $pendaftars = Pendaftar::with(['cabang', 'jenjang'])
            ->whereNotNull('nik')
            ->get(['id', 'nik', 'nama', 'cabang_id', 'jenjang_id', 'personal_data', 'education_data'])
            ->map(function ($p) {
                return [
                    'id' => (string) $p->id,
                    'nik' => (string) $p->nik,
                    'nama' => (string) $p->nama,
                    'cabang' => (string) ($p->cabang?->name ?? $p->cabang?->singkatan ?? $p->personal_data['cabang_pendaftaran'] ?? ''),
                    'jenjang' => (string) ($p->jenjang?->name ?? $p->jenjang?->code ?? $p->jenjang?->singkatan ?? $p->education_data['jenjang'] ?? ''),
                ];
            });

        return Inertia::render('Admin/Keuangan/VirtualAccount/Import', [
            'banks' => $banks,
            'cabangs' => $cabangs,
            'jenjangs' => $jenjangs,
            'pendaftars' => $pendaftars,
        ]);
    }

    public function importSubmit(Request $request)
    {
        $validated = $request->validate([
            'rows' => 'required|array',
            'rows.*.pendaftar_id' => 'required',
            'rows.*.vas' => 'required|array',
            'rows.*.vas.*.bank_id' => ['required', Rule::exists('banks', 'id')->where('is_active', true)],
            'rows.*.vas.*.nomor_va' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $totalCount = 0;
            foreach ($validated['rows'] as $row) {
                $pendaftar = Pendaftar::where('nik', $row['pendaftar_id'])
                    ->orWhere('id', $row['pendaftar_id'])
                    ->orWhere('nomor_pendaftaran', $row['pendaftar_id'])
                    ->first();

                if (! $pendaftar) {
                    continue;
                }

                foreach ($row['vas'] as $item) {
                    $nomorVa = trim($item['nomor_va'] ?? '');
                    if ($nomorVa !== '') {
                        VirtualAccount::updateOrCreate(
                            ['pendaftar_id' => $pendaftar->id, 'bank_id' => $item['bank_id']],
                            ['nomor_va' => $nomorVa]
                        );
                        $totalCount++;
                    }
                }
            }

            activity()
                ->useLog('Virtual Account')
                ->event('imported')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'total' => $totalCount])
                ->log("Mengimport {$totalCount} data Virtual Account");
        });

        return redirect()->route('va.index')->with('success', 'Import Virtual Account berhasil diproses.');
    }

    public function downloadTemplate()
    {
        return Excel::download(
            new VirtualAccountTemplateExport,
            'Template_Import_Virtual_Account.xlsx'
        );
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids');

        $fileName = 'Data_Virtual_Account_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new VirtualAccountExport($ids),
            $fileName
        );
    }
}
