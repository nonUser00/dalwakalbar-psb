<?php

namespace App\Http\Controllers\Psb;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Http\Controllers\Controller;
use App\Models\Keuangan\Pembayaran;
use App\Models\Keuangan\Tagihan;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KeuanganController extends Controller
{
    public function index()
    {
        return redirect()->route('psb.keuangan.tagihan');
    }

    public function tagihan(Request $request)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load(['cabang', 'jenjang', 'periode', 'gelombang', 'virtualAccounts.bank.biayaAdmins']);

        $search = $request->input('search');
        $status = $request->input('status');
        $kategori = $request->input('kategori');
        $dueStatus = $request->input('due_status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $limit = $request->input('limit', 10);

        // Active tagihan (hanya yang belum lunas/selesai)
        $query = Tagihan::with(['items.itemBiaya.kategoriBiaya', 'pembayarans.bank'])
            ->where('pendaftar_id', $pendaftar->id)
            ->whereNotIn('status', ['PAID', 'LUNAS', 'SAMAHA']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('description', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            if ($status === 'overdue') {
                $query->whereNotNull('due_date')
                    ->where('due_date', '<', now());
            } else {
                $query->where('status', $status);
            }
        }

        if ($kategori) {
            $query->whereHas('items.itemBiaya.kategoriBiaya', function ($kq) use ($kategori) {
                $kq->where('jenis', $kategori);
            });
        }

        if ($dueStatus) {
            if ($dueStatus === 'overdue') {
                $query->whereNotNull('due_date')->where('due_date', '<', now());
            } elseif ($dueStatus === 'today') {
                $query->whereDate('due_date', now());
            } elseif ($dueStatus === 'upcoming') {
                $query->whereNotNull('due_date')->where('due_date', '>', now());
            }
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $tagihans = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return Inertia::render('Psb/Keuangan/Tagihan', [
            'pendaftar' => $pendaftar,
            'tagihans' => $tagihans,
            'filters' => [
                'search' => (string) ($search ?? ''),
                'status' => (string) ($status ?? ''),
                'kategori' => (string) ($kategori ?? ''),
                'due_status' => (string) ($dueStatus ?? ''),
                'start_date' => (string) ($startDate ?? ''),
                'end_date' => (string) ($endDate ?? ''),
                'limit' => (int) $limit,
            ],
        ]);
    }

    public function showTagihan(Request $request, Tagihan $tagihan)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        // Pastikan tagihan milik pendaftar ini dan merupakan tagihan aktif (bukan riwayat lunas)
        if ($tagihan->pendaftar_id !== $pendaftar->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (in_array($tagihan->status?->value ?? $tagihan->status, ['PAID', 'LUNAS', 'SAMAHA'])) {
            // Jika sudah lunas/riwayat, arahkan ke detail riwayat
            return redirect()->route('psb.keuangan.riwayat.show', $tagihan->id);
        }

        $tagihan->load(['items', 'pembayarans.bank']);
        $pendaftar->load(['cabang', 'jenjang', 'periode', 'gelombang', 'dokumens.dokumen', 'virtualAccounts.bank.biayaAdmins']);

        // Backfill payment nomor_va from candidate's virtual accounts if missing
        foreach ($tagihan->pembayarans as $pembayaran) {
            if (empty($pembayaran->nomor_va) && $pembayaran->bank_id) {
                $matchingVa = $pendaftar->virtualAccounts->firstWhere('bank_id', $pembayaran->bank_id);
                if ($matchingVa) {
                    $pembayaran->nomor_va = $matchingVa->nomor_va;
                }
            }
        }

        return Inertia::render('Psb/Keuangan/DetailTagihan', [
            'pendaftar' => $pendaftar,
            'tagihan' => $tagihan,
            'virtualAccounts' => $pendaftar->virtualAccounts,
            'isRiwayat' => false,
        ]);
    }

    public function bayar(Request $request, Tagihan $tagihan)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        // Pastikan tagihan milik pendaftar ini
        if ($tagihan->pendaftar_id !== $pendaftar->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $tagihan->load(['items', 'pembayarans.bank']);
        $pendaftar->load(['cabang', 'jenjang', 'periode', 'gelombang', 'dokumens.dokumen', 'virtualAccounts.bank.biayaAdmins']);

        return Inertia::render('Psb/Keuangan/Bayar', [
            'pendaftar' => $pendaftar,
            'tagihan' => $tagihan,
            'virtualAccounts' => $pendaftar->virtualAccounts,
        ]);
    }

    public function editPembayaran(Request $request, Pembayaran $pembayaran)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        // Hanya boleh edit jika milik pendaftar ini dan statusnya MENUNGGU_VERIFIKASI
        if ($pembayaran->pendaftar_id !== $pendaftar->id || $pembayaran->status !== StatusPembayaran::MenungguVerifikasi) {
            abort(404);
        }

        $tagihan = $pembayaran->tagihan;
        $tagihan->load(['items', 'pembayarans.bank']);
        $pendaftar->load(['cabang', 'jenjang', 'periode', 'gelombang', 'dokumens.dokumen', 'virtualAccounts.bank.biayaAdmins']);

        return Inertia::render('Psb/Keuangan/Bayar', [
            'pendaftar' => $pendaftar,
            'tagihan' => $tagihan,
            'virtualAccounts' => $pendaftar->virtualAccounts,
            'pembayaran' => $pembayaran,
        ]);
    }

    public function updatePembayaran(Request $request, Pembayaran $pembayaran)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        if ($pembayaran->pendaftar_id !== $pendaftar->id || $pembayaran->status !== StatusPembayaran::MenungguVerifikasi) {
            abort(404);
        }

        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'amount' => 'required|numeric|min:0',
            'file' => 'nullable|image|max:5120',
            'redirect_to' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('bukti_pembayaran', 'public');
            $pembayaran->proof_path = $path;
        }

        $matchingVa = $pendaftar->virtualAccounts->firstWhere('bank_id', $request->bank_id);

        $pembayaran->bank_id = $request->bank_id;
        $pembayaran->amount = $request->amount;
        $pembayaran->nomor_va = $matchingVa?->nomor_va;
        $pembayaran->payment_method = MetodePembayaran::Transfer;
        $pembayaran->save();

        $redirectTo = $request->input('redirect_to');
        if ($redirectTo && (str_starts_with($redirectTo, '/psb/keuangan/') || str_starts_with($redirectTo, url('/psb/keuangan/')))) {
            return redirect($redirectTo)->with('success', 'Pembayaran berhasil diperbarui.');
        }

        return redirect()->route('psb.keuangan.tagihan.show', $pembayaran->tagihan_id)->with('success', 'Pembayaran berhasil diperbarui.');
    }

    public function destroyPembayaran(Request $request, Pembayaran $pembayaran)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        if ($pembayaran->pendaftar_id !== $pendaftar->id || $pembayaran->status !== StatusPembayaran::MenungguVerifikasi) {
            abort(404);
        }

        $tagihanId = $pembayaran->tagihan_id;
        $pembayaran->delete();

        return redirect()->back()->with('success', 'Data pembayaran berhasil dibatalkan dan dihapus.');
    }

    public function virtualAccount()
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load(['cabang', 'jenjang', 'periode', 'gelombang', 'virtualAccounts.bank']);

        $tagihans = Tagihan::with(['items'])
            ->where('pendaftar_id', $pendaftar->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Psb/Keuangan/VirtualAccount', [
            'pendaftar' => $pendaftar,
            'virtualAccounts' => $pendaftar->virtualAccounts,
            'tagihans' => $tagihans,
        ]);
    }

    public function riwayat(Request $request)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load(['cabang', 'jenjang', 'periode', 'gelombang']);

        $search = $request->input('search');
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $limit = $request->input('limit', 10);

        // Query tagihan selesai / riwayat
        $baseQuery = Tagihan::with(['items', 'pembayarans.bank'])
            ->where('pendaftar_id', $pendaftar->id)
            ->whereIn('status', ['PAID', 'LUNAS', 'SAMAHA']);

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('description', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $baseQuery->where('status', $status);
        }

        if ($startDate) {
            $baseQuery->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $baseQuery->whereDate('created_at', '<=', $endDate);
        }

        $tagihans = (clone $baseQuery)->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        // Statistics terpengaruh filter
        $filteredRiwayat = (clone $baseQuery)->get();
        $totalLunasCount = $filteredRiwayat->whereIn('status', ['PAID', 'LUNAS'])->count();
        $totalSamahaCount = $filteredRiwayat->where('status', 'SAMAHA')->count();
        $totalNominalLunas = (float) $filteredRiwayat->sum(fn ($t) => (float) ($t->total_amount ?? $t->amount ?? 0));

        // Trend Pembayaran per Bulan untuk Chart yang sinkron dengan rentang waktu
        $chartYear = $startDate ? (int) date('Y', strtotime($startDate)) : (int) date('Y');
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthKey = date('M', mktime(0, 0, 0, $m, 1));
            $pQuery = Pembayaran::where('pendaftar_id', $pendaftar->id)
                ->where('status', StatusPembayaran::Diterima)
                ->whereYear('payment_date', $chartYear)
                ->whereMonth('payment_date', $m);

            if ($startDate) {
                $pQuery->whereDate('payment_date', '>=', $startDate);
            }
            if ($endDate) {
                $pQuery->whereDate('payment_date', '<=', $endDate);
            }

            $monthlyTotal = (float) $pQuery->sum('amount');
            $monthlyData[] = [
                'month' => $monthKey,
                'total' => $monthlyTotal,
            ];
        }

        return Inertia::render('Psb/Keuangan/Riwayat', [
            'pendaftar' => $pendaftar,
            'tagihans' => $tagihans,
            'filters' => [
                'search' => (string) ($search ?? ''),
                'status' => (string) ($status ?? ''),
                'start_date' => (string) ($startDate ?? ''),
                'end_date' => (string) ($endDate ?? ''),
                'limit' => (int) $limit,
            ],
            'stats' => [
                'total_riwayat' => $filteredRiwayat->count(),
                'total_lunas' => $totalLunasCount,
                'total_samaha' => $totalSamahaCount,
                'total_nominal_selesai' => $totalNominalLunas,
            ],
            'chartYear' => $chartYear,
            'chartData' => $monthlyData,
        ]);
    }

    public function showRiwayatTagihan(Request $request, Tagihan $tagihan)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        // Pastikan tagihan milik pendaftar ini dan berstatus riwayat/lunas
        if ($tagihan->pendaftar_id !== $pendaftar->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (! in_array($tagihan->status?->value ?? $tagihan->status, ['PAID', 'LUNAS', 'SAMAHA'])) {
            // Jika belum lunas, arahkan ke detail tagihan aktif
            return redirect()->route('psb.keuangan.tagihan.show', $tagihan->id);
        }

        $tagihan->load(['items', 'pembayarans.bank']);
        $pendaftar->load(['cabang', 'jenjang', 'periode', 'gelombang', 'dokumens.dokumen', 'virtualAccounts.bank.biayaAdmins']);

        // Backfill payment nomor_va from candidate's virtual accounts if missing
        foreach ($tagihan->pembayarans as $pembayaran) {
            if (empty($pembayaran->nomor_va) && $pembayaran->bank_id) {
                $matchingVa = $pendaftar->virtualAccounts->firstWhere('bank_id', $pembayaran->bank_id);
                if ($matchingVa) {
                    $pembayaran->nomor_va = $matchingVa->nomor_va;
                }
            }
        }

        return Inertia::render('Psb/Keuangan/DetailTagihan', [
            'pendaftar' => $pendaftar,
            'tagihan' => $tagihan,
            'virtualAccounts' => $pendaftar->virtualAccounts,
            'isRiwayat' => true,
        ]);
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'tagihan_id' => 'required|exists:tagihans,id',
            'bank_id' => 'required|exists:banks,id',
            'amount' => 'required|numeric|min:0',
            'file' => 'required|image|max:5120', // 5MB max
            'redirect_to' => 'nullable|string',
        ]);

        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        // Ensure the tagihan belongs to the pendaftar
        $tagihan = Tagihan::where('id', $request->tagihan_id)
            ->where('pendaftar_id', $pendaftar->id)
            ->firstOrFail();

        $path = $request->file('file')->store('bukti_pembayaran', 'public');

        $matchingVa = $pendaftar->virtualAccounts->firstWhere('bank_id', $request->bank_id);

        Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'pendaftar_id' => $pendaftar->id,
            'bank_id' => $request->bank_id,
            'amount' => $request->amount,
            'proof_path' => $path,
            'payment_date' => now(),
            'status' => StatusPembayaran::MenungguVerifikasi,
            'payment_method' => MetodePembayaran::Transfer,
            'nomor_va' => $matchingVa?->nomor_va,
        ]);

        $redirectTo = $request->input('redirect_to');
        if ($redirectTo && (str_starts_with($redirectTo, '/psb/keuangan/') || str_starts_with($redirectTo, url('/psb/keuangan/')))) {
            return redirect($redirectTo)->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
        }

        return redirect()->route('psb.keuangan.tagihan.show', $tagihan->id)->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
    }
}
