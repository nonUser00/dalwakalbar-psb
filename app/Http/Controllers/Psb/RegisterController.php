<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PendaftarStatus;
use App\Enums\StatusPeriode;
use App\Enums\TipePendaftaran;
use App\Http\Controllers\Controller;
use App\Models\Master\TahunAkademik;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Pendaftaran\Periode;
use App\Services\NumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService
    ) {}

    public function showRegistrationForm(): Response
    {
        $waveInfo = $this->getActiveRegistrationWave();

        return Inertia::render('Psb/Auth/Register', [
            'waveInfo' => [
                'is_open' => $waveInfo['is_open'],
                'tahun_akademik' => $waveInfo['tahun_akademik'],
                'periode' => $waveInfo['periode'],
                'gelombang' => $waveInfo['gelombang'],
                'message' => $waveInfo['message'],
            ],
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $waveInfo = $this->getActiveRegistrationWave();

        if (! $waveInfo['is_open'] || ! $waveInfo['periode_model']) {
            throw ValidationException::withMessages([
                'nik' => $waveInfo['message'] ?? 'Pendaftaran saat ini sedang ditutup atau belum ada gelombang aktif dalam rentang waktu saat ini.',
            ]);
        }

        $request->validate([
            'nik' => ['required', 'string', 'digits:16', 'unique:pendaftars,nik'],
            'nama' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus berjumlah 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar dalam sistem.',
            'nama.required' => 'Nama lengkap calon santri wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ]);

        $pendaftar = DB::transaction(function () use ($request, $waveInfo) {
            $nomorPendaftaran = $this->numberingService->generateNomorPendaftaran();

            $pendaftar = Pendaftar::create([
                'nomor_pendaftaran' => $nomorPendaftaran,
                'nik' => $request->nik,
                'nama' => $request->nama,
                'password' => Hash::make($request->password),
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => TipePendaftaran::Reguler,
                'periode_id' => $waveInfo['periode_model']->id,
                'gelombang_id' => $waveInfo['gelombang_model']?->id,
            ]);

            activity()
                ->useLog('Auth')
                ->causedBy($pendaftar)
                ->event('register')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'nama' => $pendaftar->nama,
                    'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                    'nik' => $pendaftar->nik,
                    'tahun_akademik' => $waveInfo['tahun_akademik']['name'] ?? null,
                    'periode' => $waveInfo['periode']['name'] ?? null,
                    'gelombang' => $waveInfo['gelombang']['name'] ?? null,
                ])
                ->log("Pendaftar {$pendaftar->nama} ({$pendaftar->nomor_pendaftaran}) berhasil melakukan registrasi akun");

            return $pendaftar;
        });

        Auth::guard('pendaftar')->login($pendaftar);

        return redirect()->route('psb.register.success');
    }

    /**
     * Cari tahun akademik aktif, periode yang buka dan dalam rentang tanggal,
     * serta gelombang yang saat ini aktif dalam rentang tanggalnya.
     *
     * @return array{
     *     is_open: bool,
     *     tahun_akademik: ?array{id: string, name: string},
     *     periode: ?array{id: string, name: string, start_date: ?string, end_date: ?string},
     *     gelombang: ?array{id: string, name: string, start_date: ?string, end_date: ?string},
     *     periode_model: ?Periode,
     *     gelombang_model: ?Gelombang,
     *     message: ?string
     * }
     */
    protected function getActiveRegistrationWave(): array
    {
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();

        if (! $activeTahunAkademik) {
            return [
                'is_open' => false,
                'tahun_akademik' => null,
                'periode' => null,
                'gelombang' => null,
                'periode_model' => null,
                'gelombang_model' => null,
                'message' => 'Pendaftaran ditutup karena belum ada Tahun Akademik aktif yang dibuka.',
            ];
        }

        $now = now()->startOfDay();

        // Cari seluruh periode yang berstatus 'buka' pada Tahun Akademik aktif
        $openPeriodes = Periode::where('tahun_akademik_id', $activeTahunAkademik->id)
            ->where(function ($q) {
                $q->where('status', StatusPeriode::Buka)
                    ->orWhere('status', 'buka');
            })
            ->with(['gelombangs'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($openPeriodes->isEmpty()) {
            return [
                'is_open' => false,
                'tahun_akademik' => [
                    'id' => $activeTahunAkademik->id,
                    'name' => $activeTahunAkademik->name,
                ],
                'periode' => null,
                'gelombang' => null,
                'periode_model' => null,
                'gelombang_model' => null,
                'message' => 'Pendaftaran saat ini sedang ditutup karena belum ada periode pendaftaran yang dibuka.',
            ];
        }

        foreach ($openPeriodes as $periode) {
            // Periksa tanggal periode
            $pStart = $periode->start_date?->copy()->startOfDay();
            $pEnd = $periode->end_date?->copy()->endOfDay();

            if ($pStart && $now->lessThan($pStart)) {
                continue; // Periode belum dimulai
            }
            if ($pEnd && $now->greaterThan($pEnd)) {
                continue; // Periode sudah berakhir
            }

            // Periksa gelombang dalam periode ini
            $gelombangs = $periode->gelombangs;

            foreach ($gelombangs as $gelombang) {
                $gStart = ($gelombang->start_date ? $gelombang->start_date->copy()->startOfDay() : null) ?? $pStart;
                $gEnd = ($gelombang->end_date ? $gelombang->end_date->copy()->endOfDay() : null) ?? $pEnd;

                $isInRange = true;
                if ($gStart && $gEnd) {
                    $isInRange = $now->between($gStart, $gEnd);
                } elseif ($gStart) {
                    $isInRange = $now->greaterThanOrEqualTo($gStart);
                } elseif ($gEnd) {
                    $isInRange = $now->lessThanOrEqualTo($gEnd);
                }

                if ($isInRange) {
                    return [
                        'is_open' => true,
                        'tahun_akademik' => [
                            'id' => $activeTahunAkademik->id,
                            'name' => $activeTahunAkademik->name,
                        ],
                        'periode' => [
                            'id' => $periode->id,
                            'name' => $periode->name,
                            'start_date' => $periode->start_date?->translatedFormat('d F Y'),
                            'end_date' => $periode->end_date?->translatedFormat('d F Y'),
                        ],
                        'gelombang' => [
                            'id' => $gelombang->id,
                            'name' => $gelombang->name,
                            'start_date' => $gelombang->start_date?->translatedFormat('d F Y'),
                            'end_date' => $gelombang->end_date?->translatedFormat('d F Y'),
                        ],
                        'periode_model' => $periode,
                        'gelombang_model' => $gelombang,
                        'message' => null,
                    ];
                }
            }

            // Jika periode buka dan dalam range tanggal tapi tidak memiliki list gelombang (fallback)
            if ($gelombangs->isEmpty()) {
                return [
                    'is_open' => true,
                    'tahun_akademik' => [
                        'id' => $activeTahunAkademik->id,
                        'name' => $activeTahunAkademik->name,
                    ],
                    'periode' => [
                        'id' => $periode->id,
                        'name' => $periode->name,
                        'start_date' => $periode->start_date?->translatedFormat('d F Y'),
                        'end_date' => $periode->end_date?->translatedFormat('d F Y'),
                    ],
                    'gelombang' => null,
                    'periode_model' => $periode,
                    'gelombang_model' => null,
                    'message' => null,
                ];
            }
        }

        return [
            'is_open' => false,
            'tahun_akademik' => [
                'id' => $activeTahunAkademik->id,
                'name' => $activeTahunAkademik->name,
            ],
            'periode' => null,
            'gelombang' => null,
            'periode_model' => null,
            'gelombang_model' => null,
            'message' => 'Pendaftaran saat ini sedang ditutup atau belum memasuki jadwal rentang waktu gelombang pendaftaran.',
        ];
    }

    public function showSuccessPage(): Response|RedirectResponse
    {
        /** @var Pendaftar|null $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        if (! $pendaftar) {
            return redirect()->route('psb.login');
        }

        return Inertia::render('Psb/Auth/RegisterSuccess', [
            'pendaftar' => [
                'id' => $pendaftar->id,
                'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                'nik' => $pendaftar->nik,
                'nama' => $pendaftar->nama,
                'status' => $pendaftar->status instanceof PendaftarStatus ? $pendaftar->status->value : (string) $pendaftar->status,
                'status_label' => $pendaftar->status instanceof PendaftarStatus ? $pendaftar->status->label() : (string) $pendaftar->status,
                'tipe_pendaftaran' => $pendaftar->tipe_pendaftaran instanceof TipePendaftaran ? $pendaftar->tipe_pendaftaran->value : ($pendaftar->tipe_pendaftaran ?? 'Reguler'),
                'created_at' => $pendaftar->created_at ? $pendaftar->created_at->translatedFormat('d F Y, H:i').' WIB' : now()->translatedFormat('d F Y, H:i').' WIB',
            ],
        ]);
    }
}
