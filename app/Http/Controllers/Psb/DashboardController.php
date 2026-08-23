<?php

namespace App\Http\Controllers\Psb;

use App\Enums\JalurPendaftaran;
use App\Enums\PendaftarStatus;
use App\Enums\StatusPeriode;
use App\Enums\TipePendaftaran;
use App\Http\Controllers\Controller;
use App\Models\Master\Dokumen;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Pendaftaran\Periode;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        if (! $pendaftar->periode_id || ! $pendaftar->gelombang_id) {
            $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();
            if ($activeTahunAkademik) {
                $activePeriode = Periode::where('tahun_akademik_id', $activeTahunAkademik->id)
                    ->where(function ($q) {
                        $q->where('status', StatusPeriode::Buka)
                            ->orWhere('status', 'buka');
                    })
                    ->latest('created_at')
                    ->first()
                    ?? Periode::where('tahun_akademik_id', $activeTahunAkademik->id)->latest('created_at')->first();

                if ($activePeriode) {
                    $pendaftar->periode_id = $pendaftar->periode_id ?? $activePeriode->id;

                    if (! $pendaftar->gelombang_id) {
                        $now = now()->startOfDay();
                        $gelombangs = Gelombang::where('periode_id', $activePeriode->id)->get();
                        $activeGelombang = $gelombangs->first(function ($g) use ($now) {
                            $startDate = $g->start_date ?? $g->periode?->start_date;
                            $endDate = $g->end_date ?? $g->periode?->end_date;
                            if ($startDate && $endDate) {
                                return $now->between($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay());
                            }

                            return true;
                        }) ?? $gelombangs->first();

                        if ($activeGelombang) {
                            $pendaftar->gelombang_id = $activeGelombang->id;
                        }
                    }
                    $pendaftar->saveQuietly();
                }
            }
        }

        $pendaftar->load(['jenjang', 'periode', 'gelombang', 'cabang', 'tagihans.pembayarans', 'dokumens.dokumen', 'hasilUjian', 'keberangkatan']);

        // Check biodata completion: 5 steps of registration form
        $hasPersonalData = ! empty($pendaftar->personal_data) && ! empty($pendaftar->personal_data['tempat_lahir'] ?? null);
        $hasParentData = ! empty($pendaftar->parent_data) && (! empty($pendaftar->parent_data['nama_ayah'] ?? null) || ! empty($pendaftar->parent_data['nama_ibu'] ?? null));
        $hasAddressData = ! empty($pendaftar->address_data) && (! empty($pendaftar->address_data['alamat'] ?? null) || ! empty($pendaftar->address_data['desa_kelurahan'] ?? null));
        $hasEducationData = ! empty($pendaftar->education_data) && (
            ! empty($pendaftar->education_data['nama_sekolah_asal'] ?? null) ||
            ! empty($pendaftar->education_data['asal_sekolah'] ?? null) ||
            ! empty($pendaftar->education_data['kelas_tingkat'] ?? null) ||
            ! empty($pendaftar->education_data['prodi'] ?? null) ||
            ! empty($pendaftar->jenjang_id)
        );
        $isBiodataComplete = $hasPersonalData && $hasParentData && $hasAddressData && $hasEducationData;

        // Check applicable documents according to candidate's jenjang & tipe_pendaftaran
        $applicableDocsQuery = Dokumen::query()->where(function ($q) use ($pendaftar) {
            if ($pendaftar->jenjang_id) {
                $q->whereHas('jenjangs', fn ($jq) => $jq->where('jenjang_id', $pendaftar->jenjang_id))
                    ->orWhereDoesntHave('jenjangs');
            }
        });

        $tipeVal = $pendaftar->tipe_pendaftaran instanceof TipePendaftaran
            ? $pendaftar->tipe_pendaftaran->value
            : (string) ($pendaftar->tipe_pendaftaran ?? 'Reguler');

        $applicableDocs = $applicableDocsQuery->get()->filter(function ($doc) use ($tipeVal) {
            $docJalur = $doc->jalur_pendaftaran instanceof JalurPendaftaran
                ? $doc->jalur_pendaftaran->value
                : (string) ($doc->jalur_pendaftaran ?? 'Semua');

            return strcasecmp($docJalur, 'Semua') === 0 || strcasecmp($docJalur, $tipeVal) === 0;
        });

        $totalRequiredDocs = $applicableDocs->where('is_required', true)->count();
        $applicableDocIds = $applicableDocs->pluck('id');

        $uploadedDocsCount = $pendaftar->dokumens
            ->whereIn('dokumen_id', $applicableDocIds)
            ->filter(fn ($d) => ! empty($d->file_path))
            ->count();

        $isDocsComplete = ($totalRequiredDocs === 0) || ($uploadedDocsCount >= $totalRequiredDocs);

        // Check payments
        $totalTagihan = (float) $pendaftar->tagihans->sum('total_amount');
        $totalPaid = (float) $pendaftar->tagihans->flatMap->pembayarans->where('status', 'DITERIMA')->sum('amount');
        $hasUnpaidTagihan = $pendaftar->tagihans->whereIn('status', ['BELUM_BAYAR', 'BELUM_LUNAS'])->isNotEmpty();

        // Contact WhatsApp & Working Hours
        $kontakWa = Setting::where('key', 'kontak_darurat_wa')->value('value') ?? '081234567890';
        $namaContact = Setting::where('key', 'nama_contact')->value('value') ?? "Pondok Pesantren Darullughah Wadda'wah Kalbar";
        $hariKerjaRaw = Setting::where('key', 'hari_kerja')->value('value');
        $hariKerja = $hariKerjaRaw ? (json_decode($hariKerjaRaw, true) ?: explode(',', $hariKerjaRaw)) : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $jamMulai = Setting::where('key', 'jam_kerja_mulai')->value('value') ?? '08:00';
        $jamSelesai = Setting::where('key', 'jam_kerja_selesai')->value('value') ?? '17:00';
        $jamKerjaText = (is_array($hariKerja) && count($hariKerja) > 0 ? implode(', ', $hariKerja).' (' : '').$jamMulai.' - '.$jamSelesai.' WIB'.(is_array($hariKerja) && count($hariKerja) > 0 ? ')' : '');

        // Calculate progress percentage of registration form completeness (5 steps @ 20%)
        if ($pendaftar->status && $pendaftar->status !== PendaftarStatus::Draft) {
            $progress = 100;
        } else {
            $progress = 0;
            if ($hasPersonalData) {
                $progress += 20;
            }
            if ($hasParentData) {
                $progress += 20;
            }
            if ($hasAddressData) {
                $progress += 20;
            }
            if ($hasEducationData) {
                $progress += 20;
            }
            if ($isDocsComplete) {
                $progress += 20;
            } elseif ($totalRequiredDocs > 0 && $uploadedDocsCount > 0) {
                $progress += (int) round(($uploadedDocsCount / $totalRequiredDocs) * 20);
            }
        }
        $progress = min($progress, 100);

        return Inertia::render('Psb/Dashboard/Index', [
            'pendaftar' => [
                'id' => $pendaftar->id,
                'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                'nik' => $pendaftar->nik,
                'nama' => $pendaftar->nama,
                'email' => $pendaftar->email,
                'nomor_hp' => $pendaftar->nomor_hp,
                'status' => $pendaftar->status instanceof PendaftarStatus ? $pendaftar->status->value : (string) $pendaftar->status,
                'status_label' => $pendaftar->status instanceof PendaftarStatus ? $pendaftar->status->label() : (string) $pendaftar->status,
                'status_badge' => $pendaftar->status instanceof PendaftarStatus ? $pendaftar->status->badgeClass() : 'bg-slate-100 text-slate-700',
                'tipe_pendaftaran' => $pendaftar->tipe_pendaftaran?->value ?? 'Reguler',
                'current_step' => (int) ($pendaftar->current_step ?? 1),
                'foto_url' => $pendaftar->foto_url,
                'jenjang' => $pendaftar->jenjang?->nama,
                'periode' => $pendaftar->periode?->nama,
                'gelombang' => $pendaftar->gelombang?->nama,
                'cabang' => $pendaftar->cabang?->nama,
                'personal_data' => $pendaftar->personal_data,
                'parent_data' => $pendaftar->parent_data,
                'address_data' => $pendaftar->address_data,
                'education_data' => $pendaftar->education_data,
                'submitted_at' => $pendaftar->submitted_at?->translatedFormat('d F Y, H:i'),
                'created_at' => $pendaftar->created_at?->translatedFormat('d F Y'),
            ],
            'summary' => [
                'is_biodata_complete' => $isBiodataComplete,
                'has_personal_data' => $hasPersonalData,
                'has_parent_data' => $hasParentData,
                'has_address_data' => $hasAddressData,
                'has_education_data' => $hasEducationData,
                'uploaded_docs_count' => $uploadedDocsCount,
                'total_required_docs' => $totalRequiredDocs,
                'total_tagihan' => $totalTagihan,
                'total_paid' => $totalPaid,
                'has_unpaid_tagihan' => $hasUnpaidTagihan,
                'progress_percentage' => $progress,
            ],
            'kontak' => [
                'wa' => $kontakWa,
                'nama' => $namaContact,
                'jam_kerja' => $jamKerjaText,
                'hari_kerja' => $hariKerja,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
            ],
        ]);
    }

    public function cetakKartu(): Response
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load([
            'cabang',
            'jenjang',
            'periode.tahunAkademik',
            'gelombang',
            'dokumens.dokumen',
            'kelompokUjians.pengujis',
            'kelompokUjians.koordinator',
        ]);

        return Inertia::render('Admin/Pendaftar/CetakKartu', [
            'pendaftar' => $pendaftar,
        ]);
    }
}
