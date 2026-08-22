<?php

namespace App\Http\Controllers\Psb;

use App\Enums\JalurPendaftaran;
use App\Enums\PendaftarStatus;
use App\Enums\TipePendaftaran;
use App\Http\Controllers\Controller;
use App\Models\Master\Dokumen;
use App\Models\Pendaftar\Pendaftar;
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
        $pendaftar->load(['jenjang', 'periode', 'gelombang', 'cabang', 'tagihans.pembayarans', 'dokumens.dokumen', 'hasilUjian', 'keberangkatan']);

        // Check biodata completion: complete when all 4 steps have data
        $hasPersonalData = ! empty($pendaftar->personal_data) && ! empty($pendaftar->personal_data['tempat_lahir'] ?? null);
        $hasParentData = ! empty($pendaftar->parent_data) && (! empty($pendaftar->parent_data['nama_ayah'] ?? null) || ! empty($pendaftar->parent_data['nama_ibu'] ?? null));
        $hasAddressData = ! empty($pendaftar->address_data) && ! empty($pendaftar->address_data['alamat'] ?? null);
        $hasEducationData = ! empty($pendaftar->education_data) && (
            ! empty($pendaftar->education_data['nama_sekolah_asal'] ?? null) ||
            ! empty($pendaftar->education_data['asal_sekolah'] ?? null) ||
            ! empty($pendaftar->education_data['kelas_tingkat'] ?? null) ||
            ! empty($pendaftar->education_data['prodi'] ?? null)
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

        // Calculate progress percentage (0 - 100)
        $progress = 20; // 20% for registration done (Tahap 1)
        if ($isBiodataComplete) {
            $progress += 25; // 25% for Tahap 2
        } elseif ($pendaftar->current_step > 1 || $hasPersonalData) {
            $progress += 10; // In-progress biodata
        }

        if ($totalRequiredDocs > 0 && $uploadedDocsCount >= $totalRequiredDocs) {
            $progress += 25;
        } elseif ($uploadedDocsCount > 0) {
            $progress += 15;
        }
        if (! $hasUnpaidTagihan && $totalTagihan > 0) {
            $progress += 15;
        }
        if (in_array($pendaftar->status, [PendaftarStatus::Lulus, PendaftarStatus::TidakLulus], true)) {
            $progress += 15;
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
