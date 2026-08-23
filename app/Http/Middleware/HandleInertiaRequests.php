<?php

namespace App\Http\Middleware;

use App\Enums\PendaftarStatus;
use App\Enums\StatusDokumen;
use App\Enums\StatusKelompokUjian;
use App\Enums\StatusPembayaran;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Setting\Setting;
use App\Models\Ujian\KelompokUjian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'pendaftar' => $request->user('pendaftar'),
            ],
            'app_settings' => Cache::rememberForever('app_settings', function () {
                return Setting::pluck('value', 'key')->toArray();
            }),
            'sidebar_badges' => function () use ($request) {
                if ($pendaftar = $request->user('pendaftar')) {
                    // PSB Sidebar Badges for logged-in candidate
                    $pendaftar->loadMissing(['dokumens', 'tagihans', 'kelompokUjians']);
                    $hasPersonalRevision = ! empty($pendaftar->personal_data['catatan_personal']) || ! empty($pendaftar->personal_data['catatan_revisi']);
                    $hasParentRevision = ! empty($pendaftar->parent_data['catatan_parent']);
                    $hasAddressRevision = ! empty($pendaftar->address_data['catatan_address']);
                    $hasEducationRevision = ! empty($pendaftar->education_data['catatan_education']);

                    $biodataRevisionCount = ($hasPersonalRevision ? 1 : 0)
                        + ($hasParentRevision ? 1 : 0)
                        + ($hasAddressRevision ? 1 : 0)
                        + ($hasEducationRevision ? 1 : 0);

                    $dokumenRevisionCount = $pendaftar->dokumens
                        ->filter(function ($d) {
                            $statusVal = $d->status instanceof StatusDokumen ? $d->status->value : (string) $d->status;

                            return ! empty($d->catatan) || in_array(strtoupper($statusVal), ['REJECTED', 'DITOLAK']);
                        })
                        ->count();

                    // Jumlah tagihan yang belum lunas (aktif)
                    $unpaidTagihanCount = $pendaftar->tagihans
                        ->filter(function ($t) {
                            $statusVal = $t->status instanceof StatusTagihan ? $t->status->value : (string) $t->status;

                            return in_array(strtoupper($statusVal), ['BELUM_BAYAR', 'BELUM_LUNAS', 'UNPAID', 'PARTIAL']);
                        })
                        ->count();

                    // Sesi interview yang terjadwal
                    $jadwalInterviewCount = $pendaftar->kelompokUjians->count();

                    return [
                        'psb_formulir' => $biodataRevisionCount + $dokumenRevisionCount,
                        'psb_biodata' => $biodataRevisionCount,
                        'psb_dokumen' => $dokumenRevisionCount,
                        'psb_tagihan' => $unpaidTagihanCount,
                        'psb_jadwal' => $jadwalInterviewCount,
                    ];
                }

                if (! $request->user()) {
                    return [];
                }

                return Cache::remember('admin_sidebar_badges_'.($request->user()?->id ?? 'guest'), 5, function () use ($request) {
                    try {
                        $user = $request->user();
                        $activeTA = TahunAkademik::where('is_active', true)->first();
                        $taId = $activeTA?->id;

                        // 1. Pendaftar Submit (perlu diverifikasi)
                        $submitCount = Pendaftar::accessibleBy($user)
                            ->where('status', PendaftarStatus::Submitted)
                            ->when($taId, fn ($q) => $q->whereHas('periode', fn ($pq) => $pq->where('tahun_akademik_id', $taId)))
                            ->count();

                        // 2. Pendaftar Tagihan (belum dibuat tagihan + menunggu verifikasi)
                        $tagihanBelumDibuat = Pendaftar::accessibleBy($user)
                            ->where('status', PendaftarStatus::Tagihan)
                            ->when($taId, fn ($q) => $q->whereHas('periode', fn ($pq) => $pq->where('tahun_akademik_id', $taId)))
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
                            ->count();

                        $tagihanPerluVerifikasi = Pendaftar::accessibleBy($user)
                            ->where('status', PendaftarStatus::Tagihan)
                            ->when($taId, fn ($q) => $q->whereHas('periode', fn ($pq) => $pq->where('tahun_akademik_id', $taId)))
                            ->whereHas('tagihans.pembayarans', fn ($q) => $q->where('status', StatusPembayaran::MenungguVerifikasi->value))
                            ->count();

                        $tagihanCount = $tagihanBelumDibuat + $tagihanPerluVerifikasi;

                        // 3. Set Interview (belum dijadwalkan / antrean)
                        $setInterviewCount = Pendaftar::accessibleBy($user)
                            ->where('status', PendaftarStatus::Interview)
                            ->when($taId, fn ($q) => $q->whereHas('periode', fn ($pq) => $pq->where('tahun_akademik_id', $taId)))
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

                        // 4. Penilaian Interview (jumlah kelompok yang dalam masa jadwal - tidak terpengaruh filter izin pendaftar)
                        $penilaianInterviewCount = KelompokUjian::whereIn('status', [
                            StatusKelompokUjian::Scheduled,
                            StatusKelompokUjian::InProgress,
                        ])
                            ->when($taId, fn ($q) => $q->whereHas('pendaftars.periode', fn ($pq) => $pq->where('tahun_akademik_id', $taId)))
                            ->count();

                        return [
                            'pendaftar_submit' => $submitCount,
                            'pendaftar_tagihan' => $tagihanCount,
                            'set_interview' => $setInterviewCount,
                            'penilaian_interview' => $penilaianInterviewCount,
                        ];
                    } catch (\Throwable $e) {
                        return [];
                    }
                });
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
