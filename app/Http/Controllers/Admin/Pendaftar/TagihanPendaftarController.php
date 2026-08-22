<?php

namespace App\Http\Controllers\Admin\Pendaftar;

use App\Enums\PendaftarStatus;
use App\Http\Controllers\Controller;
use App\Models\Keuangan\Pembayaran;
use App\Models\Keuangan\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TagihanPendaftarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pendaftar.view', only: ['show']),
            new Middleware('permission:pendaftar.edit', only: ['verifyPayment', 'addPayment']),
        ];
    }

    public function show(Tagihan $tagihan)
    {
        $tagihan->load(['pendaftar.program', 'pendaftar.gelombang', 'pembayarans.verifiedBy', 'pembayarans.creator', 'items']);

        return Inertia::render('Admin/Tagihan/Show', [
            'tagihan' => $tagihan,
        ]);
    }

    public function verifyPayment(Request $request, Pembayaran $pembayaran)
    {
        $validated = $request->validate([
            'action' => 'required|in:terima,tolak,kembalikan',
            'alasan_penolakan' => 'required_if:action,tolak|nullable|string',
            'amount_verified' => 'required_if:action,terima|numeric|min:0',
        ]);

        DB::transaction(function () use ($pembayaran, $validated, $request) {
            $tagihan = $pembayaran->tagihan;
            $oldStatus = $pembayaran->status;

            if ($validated['action'] === 'terima') {
                $pembayaran->update([
                    'status' => 'DITERIMA',
                    'amount' => $validated['amount_verified'] ?? $pembayaran->amount,
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);

                // Update Tagihan status
                $totalPaid = (float) $tagihan->pembayarans()->where('status', 'DITERIMA')->sum('amount');
                $pendaftar = $tagihan->pendaftar;
                if ($totalPaid >= (float) $tagihan->total_amount && (float) $tagihan->total_amount > 0) {
                    $tagihan->update(['status' => 'LUNAS']);
                    if ($pendaftar && in_array($pendaftar->status?->value ?? $pendaftar->status, ['TAGIHAN', 'SUBMITTED', 'DRAFT'], true)) {
                        $pendaftar->update(['status' => PendaftarStatus::Interview]);
                    }
                } else {
                    $tagihan->update(['status' => 'BELUM_LUNAS']);
                    if ($pendaftar && ($pendaftar->status?->value ?? $pendaftar->status) === 'INTERVIEW' && ! $pendaftar->hasil_ujian && ! $pendaftar->kelompokUjians()->exists()) {
                        $pendaftar->update(['status' => PendaftarStatus::Tagihan]);
                    }
                }

                $logMessage = "Menerima pembayaran tagihan {$tagihan->nomor_invoice}";
            } elseif ($validated['action'] === 'tolak') {
                $pembayaran->update([
                    'status' => 'DITOLAK',
                    'catatan' => $validated['alasan_penolakan'],
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);

                // Recalculate tagihan status
                $totalPaid = (float) $tagihan->pembayarans()->where('status', 'DITERIMA')->sum('amount');
                $pendaftar = $tagihan->pendaftar;
                if ($totalPaid <= 0) {
                    $tagihan->update(['status' => 'BELUM_BAYAR']);
                } else {
                    $tagihan->update(['status' => 'BELUM_LUNAS']);
                }
                if ($pendaftar && ($pendaftar->status?->value ?? $pendaftar->status) === 'INTERVIEW' && ! $pendaftar->hasil_ujian && ! $pendaftar->kelompokUjians()->exists()) {
                    $pendaftar->update(['status' => PendaftarStatus::Tagihan]);
                }

                $logMessage = "Menolak pembayaran tagihan {$tagihan->nomor_invoice}";
            } else {
                $pembayaran->update([
                    'status' => 'MENUNGGU_VERIFIKASI',
                    'catatan' => $validated['alasan_penolakan'],
                    'verified_at' => null,
                    'verified_by' => null,
                ]);

                // Recalculate tagihan status
                $totalPaid = (float) $tagihan->pembayarans()->where('status', 'DITERIMA')->sum('amount');
                $pendaftar = $tagihan->pendaftar;
                if ($totalPaid <= 0) {
                    $tagihan->update(['status' => 'BELUM_BAYAR']);
                } else {
                    $tagihan->update(['status' => 'BELUM_LUNAS']);
                }
                if ($pendaftar && ($pendaftar->status?->value ?? $pendaftar->status) === 'INTERVIEW' && ! $pendaftar->hasil_ujian && ! $pendaftar->kelompokUjians()->exists()) {
                    $pendaftar->update(['status' => PendaftarStatus::Tagihan]);
                }

                $logMessage = "Mengembalikan status pembayaran tagihan {$tagihan->nomor_invoice}";
            }

            activity()->useLog('Keuangan')->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'pembayaran_id' => $pembayaran->id,
                    'old_status' => $oldStatus,
                    'new_status' => $pembayaran->status,
                ])
                ->log($logMessage);
        });

        return back()->with('success', 'Verifikasi pembayaran berhasil disimpan.');
    }

    public function addPayment(Request $request, Tagihan $tagihan)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:TUNAI,SAMAHA',
            'amount' => 'required|numeric|min:1',
            'catatan' => 'nullable|string',
        ]);

        DB::transaction(function () use ($tagihan, $validated, $request) {
            $tagihan->pembayarans()->create([
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'status' => 'DITERIMA', // Tunai/Samaha directly verified
                'tanggal_bayar' => now(),
                'catatan' => $validated['catatan'],
                'created_by' => auth()->id(),
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);

            // Update Tagihan status
            $totalPaid = (float) $tagihan->pembayarans()->where('status', 'DITERIMA')->sum('amount');
            $pendaftar = $tagihan->pendaftar;
            if ($totalPaid >= (float) $tagihan->total_amount && (float) $tagihan->total_amount > 0) {
                $tagihan->update(['status' => 'LUNAS']);
                if ($pendaftar && in_array($pendaftar->status?->value ?? $pendaftar->status, ['TAGIHAN', 'SUBMITTED', 'DRAFT'], true)) {
                    $pendaftar->update(['status' => PendaftarStatus::Interview]);
                }
            } else {
                $tagihan->update(['status' => 'BELUM_LUNAS']);
            }

            activity()->useLog('Keuangan')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()])
                ->log("Menambahkan pembayaran {$validated['payment_method']} untuk tagihan {$tagihan->nomor_invoice}");
        });

        return back()->with('success', 'Pembayaran berhasil ditambahkan.');
    }
}
