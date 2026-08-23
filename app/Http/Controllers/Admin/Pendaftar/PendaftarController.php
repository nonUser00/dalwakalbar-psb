<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Bank;
use App\Models\Keuangan\Tagihan;
use App\Models\Master\Dokumen;
use App\Models\Master\Jenjang;
use App\Models\Pendaftar\Pendaftar;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class PendaftarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pendaftar.view', only: ['index', 'show', 'cetakKartu']),
            new Middleware('permission:pendaftar.edit', only: ['resetPassword', 'verify', 'bulkCreateTagihan']),
            new Middleware('permission:pendaftar.delete', only: ['destroy']),
            new Middleware('permission:pendaftar.export', only: ['export']),
        ];
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'draft');
        $statusMapping = [
            'draft' => 'DRAFT',
            'submitted' => 'SUBMITTED',
            'document_review' => 'DOCUMENT_REVIEW',
            'verified' => 'VERIFIED',
            'tagihan' => 'TAGIHAN',
            'set_interview' => 'TAGIHAN',
            'interview' => 'INTERVIEW',
        ];
        $actualStatus = $statusMapping[$status] ?? 'DRAFT';

        $jenjangs = Jenjang::accessibleBy()->where('is_active', true)->orderBy('code')->get();

        $query = Pendaftar::accessibleBy()
            ->where('status', $actualStatus)
            ->with(['periode', 'jenjang', 'cabang', 'gelombang']);

        if ($actualStatus === 'TAGIHAN') {
            $query->with(['tagihans.pembayarans', 'tagihans.items']);
        }

        if ($status === 'tagihan') {
            // Hanya tampilkan yang belum lunas (atau semua tagihan, tapi biasanya kita butuh filter LUNAS vs belum)
            // Sesuai PRD, tab "Tagihan" status tagihan belum lunas
            $query->whereHas('tagihans', function ($q) {
                $q->where('status', '!=', 'LUNAS');
            })->orWhereDoesntHave('tagihans');
        } elseif ($status === 'set_interview') {
            $query->whereHas('tagihans', function ($q) {
                $q->where('status', 'LUNAS');
            });
        }

        if ($request->filled('jenjang_id')) {
            $query->where('jenjang_id', $request->jenjang_id);
        }

        $pendaftars = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Pendaftar/Index', [
            'pendaftars' => $pendaftars,
            'jenjangs' => $jenjangs,
            'currentStatus' => $status,
            'actualStatus' => $actualStatus,
            'filters' => $request->only(['status', 'jenjang_id']),
        ]);
    }

    public function show(Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data calon santri ini.');
        }

        $pendaftar->load([
            'cabang',
            'jenjang',
            'periode.tahunAkademik',
            'gelombang',
            'dokumens.dokumen',
            'virtualAccounts.bank',
            'tagihans.pembayarans',
        ]);

        $masterDokumens = Dokumen::with('jenjangs:id,name,code,singkatan')->get();
        $banks = Bank::where('is_active', true)
            ->orderBy('kode_bank')
            ->orderBy('singkatan')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Pendaftar/Show', [
            'pendaftar' => $pendaftar,
            'masterDokumens' => $masterDokumens,
            'banks' => $banks,
        ]);
    }

    public function cetakKartu(Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencetak kartu calon santri ini.');
        }

        $pendaftar->load([
            'cabang',
            'jenjang',
            'periode.tahunAkademik',
            'gelombang',
            'dokumens.dokumen',
            'virtualAccounts.bank',
            'tagihans.pembayarans',
        ]);

        return Inertia::render('Admin/Pendaftar/CetakKartu', [
            'pendaftar' => $pendaftar,
        ]);
    }

    public function resetPassword(Request $request, Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        DB::transaction(function () use ($pendaftar, $validated, $request) {
            $pendaftar->update([
                'password' => Hash::make($validated['password']),
            ]);

            activity()->useLog('Pendaftar')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()])
                ->log("Mereset password pendaftar: {$pendaftar->nama} ({$pendaftar->nik})");
        });

        return back()->with('success', 'Password berhasil direset.');
    }

    public function destroy(Request $request, $id)
    {
        if ($id === 'bulk') {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return back()->with('error', 'Tidak ada data yang dipilih.');
            }

            $pendaftars = Pendaftar::accessibleBy(auth()->user())->whereIn('id', $ids)->get();
            if ($pendaftars->isEmpty()) {
                return back()->with('error', 'Tidak ada data pendaftar yang memiliki hak akses untuk dihapus.');
            }

            $validIds = $pendaftars->pluck('id')->toArray();

            DB::transaction(function () use ($validIds, $request) {
                Pendaftar::whereIn('id', $validIds)->delete();

                activity()->useLog('Pendaftar')->event('deleted')
                    ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'ids' => $validIds])
                    ->log('Menghapus massal data Pendaftar');
            });

            return back()->with('success', 'Pendaftar terpilih berhasil dihapus.');
        }

        $pendaftar = Pendaftar::findOrFail($id);
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $oldData = $pendaftar->toArray();

        DB::transaction(function () use ($pendaftar, $request, $oldData) {
            $pendaftar->delete();

            activity()->useLog('Pendaftar')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log("Menghapus Pendaftar: {$oldData['nama']}");
        });

        return back()->with('success', 'Data pendaftar berhasil dihapus.');
    }

    public function export(Request $request)
    {
        // For now, return back as a placeholder
        // TODO: Implement actual Excel export using maatwebsite/excel
        return back()->with('success', 'Fungsi export akan segera tersedia.');
    }

    public function verify(Request $request, Pendaftar $pendaftar)
    {
        if (! $pendaftar->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki hak akses ke data pendaftar ini.');
        }

        $validated = $request->validate([
            'action' => 'required|in:terima,tolak',
            'alasan_penolakan' => 'required_if:action,tolak|nullable|string',
        ]);

        DB::transaction(function () use ($pendaftar, $validated, $request) {
            $oldStatus = $pendaftar->status;

            if ($validated['action'] === 'terima') {
                $pendaftar->update(['status' => 'VERIFIED']);
                $logMessage = "Memverifikasi dokumen pendaftar: {$pendaftar->nama}";
            } else {
                $pendaftar->update([
                    'status' => 'DRAFT',
                    'personal_data' => array_merge((array) $pendaftar->personal_data, ['alasan_penolakan' => $validated['alasan_penolakan']]),
                ]);
                $logMessage = "Menolak pendaftaran: {$pendaftar->nama} (Alasan: {$validated['alasan_penolakan']})";
            }

            activity()->useLog('Pendaftar')->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => ['status' => $oldStatus],
                    'attributes' => ['status' => $pendaftar->status],
                ])
                ->log($logMessage);
        });

        $message = $validated['action'] === 'terima' ? 'Pendaftaran berhasil diverifikasi.' : 'Pendaftaran berhasil ditolak.';

        return back()->with('success', $message);
    }

    public function bulkCreateTagihan(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pendaftars,id',
        ]);

        $ids = $validated['ids'];
        $createdCount = 0;

        DB::transaction(function () use ($ids, $request, &$createdCount) {
            $pendaftars = Pendaftar::whereIn('id', $ids)->where('status', 'VERIFIED')->get();

            foreach ($pendaftars as $pendaftar) {
                // Check if Tagihan already exists to avoid duplicates
                $exists = Tagihan::where('pendaftar_id', $pendaftar->id)->exists();
                if (! $exists) {
                    $tagihan = Tagihan::create([
                        'nomor_invoice' => app(NumberingService::class)->generateNomorInvoice(),
                        'pendaftar_id' => $pendaftar->id,
                        'total_amount' => 0, // Biaya will be added in Tagihan module based on items
                        'status' => 'BELUM_BAYAR',
                        'due_date' => now()->addDays(30), // Default 30 days
                        'published_at' => now(),
                        'created_by' => auth()->id(),
                    ]);

                    $pendaftar->update(['status' => 'TAGIHAN']);
                    $createdCount++;
                }
            }

            if ($createdCount > 0) {
                activity()->useLog('Pendaftar')->event('updated')
                    ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'ids' => $ids])
                    ->log("Membuat $createdCount tagihan untuk pendaftar");
            }
        });

        if ($createdCount === 0) {
            return back()->with('error', 'Tidak ada tagihan baru yang dibuat. Mungkin pendaftar belum terverifikasi atau sudah memiliki tagihan.');
        }

        return back()->with('success', "$createdCount Tagihan berhasil dibuat.");
    }
}
