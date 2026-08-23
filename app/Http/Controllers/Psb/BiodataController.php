<?php

namespace App\Http\Controllers\Psb;

use App\Enums\JalurPendaftaran;
use App\Enums\PendaftarStatus;
use App\Enums\TipePendaftaran;
use App\Http\Controllers\Controller;
use App\Models\Master\Cabang;
use App\Models\Master\Dokumen;
use App\Models\Master\Fakultas;
use App\Models\Master\Jenjang;
use App\Models\Master\Jurusan;
use App\Models\Master\PekerjaanOrtu;
use App\Models\Master\PendidikanOrtu;
use App\Models\Master\PenghasilanOrtu;
use App\Models\Master\Prodi;
use App\Models\Master\UkuranBaju;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftar\PendaftarDokumen;
use App\Models\Pendaftar\PendidikanPendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BiodataController extends Controller
{
    public function index(): Response
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();
        $pendaftar->load(['cabang', 'jenjang', 'periode.tahunAkademik', 'gelombang', 'dokumens.dokumen']);

        $orderMap = ['MTS' => 1, 'MA' => 2, 'S1' => 3, 'S2' => 4, 'S3' => 5];
        $jenjangs = Jenjang::with(['tingkats', 'jurusans', 'fakultas.prodis'])
            ->get()
            ->sortBy(fn ($item) => $orderMap[strtoupper($item->code ?? '')] ?? 99)
            ->values();

        $masterData = [
            'cabangs' => Cabang::orderBy('name')->get(),
            'ukuran_bajus' => UkuranBaju::orderBy('name')->get(),
            'pendidikan_ortus' => PendidikanOrtu::orderBy('name')->get(),
            'pekerjaan_ortus' => PekerjaanOrtu::orderBy('name')->get(),
            'penghasilan_ortus' => PenghasilanOrtu::orderBy('name')->get(),
            'pendidikan_pendaftars' => PendidikanPendaftar::with('tingkats')->orderBy('name')->get(),
            'jenjangs' => $jenjangs,
            'fakultas' => Fakultas::with('prodis')->orderBy('name')->get(),
            'jurusans' => Jurusan::orderBy('name')->get(),
            'prodis' => Prodi::orderBy('name')->get(),
        ];

        // Master documents matching candidate's jenjang & jalur pendaftaran
        $tipeVal = $pendaftar->tipe_pendaftaran instanceof TipePendaftaran
            ? $pendaftar->tipe_pendaftaran->value
            : (string) ($pendaftar->tipe_pendaftaran ?? 'Reguler');

        $masterDokumens = Dokumen::where(function ($q) use ($pendaftar) {
            if ($pendaftar->jenjang_id) {
                $q->whereHas('jenjangs', fn ($jq) => $jq->where('jenjang_id', $pendaftar->jenjang_id))
                    ->orWhereDoesntHave('jenjangs');
            }
        })
            ->get()
            ->filter(function ($doc) use ($tipeVal) {
                $docJalur = $doc->jalur_pendaftaran instanceof JalurPendaftaran
                    ? $doc->jalur_pendaftaran->value
                    : (string) ($doc->jalur_pendaftaran ?? 'Semua');

                return strcasecmp($docJalur, 'Semua') === 0 || strcasecmp($docJalur, $tipeVal) === 0;
            })
            ->values();

        return Inertia::render('Psb/Biodata/Index', [
            'pendaftar' => $pendaftar,
            'masterData' => $masterData,
            'masterDokumens' => $masterDokumens,
            'uploadedDokumens' => $pendaftar->dokumens,
        ]);
    }

    public function update(Request $request)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        // Check if locked
        if ($pendaftar->status !== PendaftarStatus::Draft && $pendaftar->status !== null) {
            return redirect()->back()->with('error', 'Formulir pendaftaran sudah disubmit dan tidak dapat diubah lagi.');
        }

        $validated = $request->validate([
            'step' => 'required|integer|min:1|max:5',
            'target_step' => 'nullable|integer|min:1|max:5',
            'data' => 'nullable|array',
        ]);

        $step = $validated['step'];
        $targetStep = $validated['target_step'] ?? min(5, $step + 1);
        $data = $validated['data'] ?? [];

        DB::transaction(function () use ($pendaftar, $step, $targetStep, $data) {
            if ($step === 1) {
                if (! empty($data['foto']) && str_starts_with($data['foto'], 'data:image')) {
                    $oldFoto = $pendaftar->personal_data['foto'] ?? null;
                    $data['foto'] = $this->handleBase64Image($data['foto'], $oldFoto);
                }

                // Pertahankan catatan revisi jika ada
                if (isset($pendaftar->personal_data['catatan_personal'])) {
                    $data['catatan_personal'] = $pendaftar->personal_data['catatan_personal'];
                }
                if (isset($pendaftar->personal_data['catatan_revisi'])) {
                    $data['catatan_revisi'] = $pendaftar->personal_data['catatan_revisi'];
                }

                $pendaftar->personal_data = $data;

                if (! empty($data['nama'])) {
                    $pendaftar->nama = $data['nama'];
                }
                if (! empty($data['nik'])) {
                    $pendaftar->nik = $data['nik'];
                }
                if (! empty($data['email'])) {
                    $pendaftar->email = $data['email'];
                }
                if (! empty($data['nomor_hp'])) {
                    $pendaftar->nomor_hp = $data['nomor_hp'];
                }
                if (! empty($data['cabang_id'])) {
                    $pendaftar->cabang_id = $data['cabang_id'];
                }
            } elseif ($step === 2) {
                if (isset($pendaftar->parent_data['catatan_parent'])) {
                    $data['catatan_parent'] = $pendaftar->parent_data['catatan_parent'];
                }
                $pendaftar->parent_data = $data;
            } elseif ($step === 3) {
                if (isset($pendaftar->address_data['catatan_address'])) {
                    $data['catatan_address'] = $pendaftar->address_data['catatan_address'];
                }
                $pendaftar->address_data = $data;
            } elseif ($step === 4) {
                if (isset($pendaftar->education_data['catatan_education'])) {
                    $data['catatan_education'] = $pendaftar->education_data['catatan_education'];
                }
                $pendaftar->education_data = $data;

                if (! empty($data['jenjang_id'])) {
                    $pendaftar->jenjang_id = $data['jenjang_id'];
                }
                if (! empty($data['tipe_pendaftaran'])) {
                    $pendaftar->tipe_pendaftaran = $data['tipe_pendaftaran'];
                }
            }

            // Update current step to target step
            $pendaftar->current_step = max((int) ($pendaftar->current_step ?? 1), (int) $targetStep);

            $pendaftar->save();
        });

        $message = 'Data langkah '.$step.' berhasil disimpan.';

        return redirect()->back()->with('success', $message);
    }

    public function uploadDokumen(Request $request)
    {
        $request->validate([
            'dokumen_id' => 'required|exists:dokumens,id',
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf,webp|max:5120', // 5MB max
        ]);

        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        if ($pendaftar->status !== PendaftarStatus::Draft && $pendaftar->status !== null) {
            return redirect()->back()->with('error', 'Formulir pendaftaran sudah disubmit dan tidak dapat diubah lagi.');
        }

        $dokumenId = $request->dokumen_id;
        $dokumenMaster = Dokumen::find($dokumenId);

        if ($dokumenMaster) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $isImage = str_starts_with($mime, 'image/');
            $isPdf = $mime === 'application/pdf';

            if ($dokumenMaster->type === 'gambar' && ! $isImage) {
                return redirect()->back()->withErrors(['file' => 'Dokumen ini hanya menerima file format gambar (JPG, PNG, WebP).']);
            }
            if ($dokumenMaster->type === 'pdf' && ! $isPdf) {
                return redirect()->back()->withErrors(['file' => 'Dokumen ini hanya menerima file format PDF.']);
            }
        }

        $path = $request->file('file')->store('pendaftar_dokumens', 'public');

        // Cari dokumen yang sudah ada
        $existing = PendaftarDokumen::where('pendaftar_id', $pendaftar->id)
            ->where('dokumen_id', $dokumenId)
            ->first();

        // Pertahankan catatan jika ada
        $catatan = $existing ? $existing->catatan : null;

        PendaftarDokumen::updateOrCreate(
            [
                'pendaftar_id' => $pendaftar->id,
                'dokumen_id' => $dokumenId,
            ],
            [
                'file_path' => $path,
                'status' => 'PENDING',
                'catatan' => $catatan,
                'verified_by' => null,
                'verified_at' => null,
            ]
        );

        return redirect()->back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function batalSubmit(Request $request)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        // Hanya izinkan batal submit jika statusnya masih SUBMITTED (belum diverifikasi/tagihan/interview/lulus dll)
        if ($pendaftar->status !== PendaftarStatus::Submitted) {
            return redirect()->back()->with('error', 'Formulir pendaftaran tidak dapat diedit kembali karena sudah diproses ke tahap selanjutnya.');
        }

        $pendaftar->update([
            'status' => PendaftarStatus::Draft,
            'locked_at' => null,
        ]);

        activity()
            ->useLog('Pendaftar')
            ->causedBy($pendaftar)
            ->event('unsubmitted')
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Pendaftar {$pendaftar->nama} ({$pendaftar->nomor_pendaftaran}) membuka kembali kunci formulir pendaftaran untuk diedit.");

        return redirect()->route('psb.biodata.index', ['step' => 1])->with('success', 'Formulir pendaftaran berhasil dibuka. Silakan periksa atau ubah data Anda.');
    }

    public function submitFinal(Request $request)
    {
        /** @var Pendaftar $pendaftar */
        $pendaftar = Auth::guard('pendaftar')->user();

        if ($pendaftar->status !== PendaftarStatus::Draft && $pendaftar->status !== null) {
            return redirect()->back()->with('error', 'Formulir pendaftaran sudah disubmit dan tidak dapat diubah lagi.');
        }

        // Hapus seluruh catatan revisi saat submit final pendaftaran
        $personal = $pendaftar->personal_data ?? [];
        unset($personal['catatan_personal'], $personal['catatan_revisi']);

        $parent = $pendaftar->parent_data ?? [];
        unset($parent['catatan_parent']);

        $address = $pendaftar->address_data ?? [];
        unset($address['catatan_address']);

        $education = $pendaftar->education_data ?? [];
        unset($education['catatan_education']);

        $pendaftar->update([
            'status' => PendaftarStatus::Submitted,
            'personal_data' => $personal,
            'parent_data' => $parent,
            'address_data' => $address,
            'education_data' => $education,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);

        // Reset catatan pada dokumen
        PendaftarDokumen::where('pendaftar_id', $pendaftar->id)
            ->update([
                'catatan' => null,
                'status' => 'PENDING',
            ]);

        activity()
            ->useLog('Pendaftar')
            ->causedBy($pendaftar)
            ->event('submitted')
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Pendaftar {$pendaftar->nama} ({$pendaftar->nomor_pendaftaran}) telah mengirim formulir pendaftaran secara final.");

        return redirect()->route('psb.dashboard')->with('success', 'Pendaftaran Anda berhasil dikirim untuk verifikasi Panitia!');
    }

    private function handleBase64Image(string $base64String, ?string $oldPath = null): ?string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64String)) {
            $image_parts = explode(';base64,', $base64String);
            $image_type_aux = explode('image/', $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $fileName = 'pendaftar-foto/'.uniqid().'.'.$image_type;
            Storage::disk('public')->put($fileName, $image_base64);

            return $fileName;
        }

        return $oldPath;
    }
}
