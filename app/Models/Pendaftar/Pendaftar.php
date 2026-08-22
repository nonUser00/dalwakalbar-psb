<?php

namespace App\Models\Pendaftar;

use App\Enums\PendaftarStatus;
use App\Enums\StatusKesehatan;
use App\Enums\TipePendaftaran;
use App\Models\Asrama\Keberangkatan;
use App\Models\Keuangan\Pembayaran;
use App\Models\Keuangan\Tagihan;
use App\Models\Keuangan\VirtualAccount;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Pendaftaran\Periode;
use App\Models\Ujian\HasilUjian;
use App\Models\Ujian\KelompokUjian;
use App\Models\Ujian\Penilaian;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pendaftar extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'nomor_pendaftaran', 'nik', 'nama', 'password', 'email', 'nomor_hp', 'status',
        'status_kesehatan', 'catatan_kesehatan', 'is_santri', 'nama_pondok', 'asrama', 'kamar',
        'periode_id', 'gelombang_id', 'cabang_id', 'jenjang_id', 'program_id', 'tipe_pendaftaran',
        'current_step', 'personal_data', 'parent_data', 'address_data', 'education_data',
        'submitted_at', 'locked_at', 'is_interview_ulang', 'interview_ulang_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'status' => PendaftarStatus::class,
        'status_kesehatan' => StatusKesehatan::class,
        'tipe_pendaftaran' => TipePendaftaran::class,
        'is_santri' => 'boolean',
        'is_interview_ulang' => 'boolean',
        'current_step' => 'integer',
        'personal_data' => 'array',
        'parent_data' => 'array',
        'address_data' => 'array',
        'education_data' => 'array',
        'submitted_at' => 'datetime',
        'locked_at' => 'datetime',
        'interview_ulang_at' => 'datetime',
    ];

    protected $appends = [
        'foto_url',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        $personal = $this->personal_data ?? [];
        $raw = $personal['foto_url'] ?? $personal['foto'] ?? $personal['pas_foto'] ?? null;

        if (! $raw && $this->relationLoaded('dokumens')) {
            $photoDoc = $this->dokumens->first(function ($d) {
                return ($d->dokumen && $d->dokumen->is_profile_photo)
                    || ($d->dokumen && str_contains(strtolower($d->dokumen->name), 'foto'))
                    || str_contains(strtolower($d->name ?? ''), 'foto')
                    || str_contains(strtolower($d->file_path ?? ''), 'foto')
                    || str_contains(strtolower($d->file_path ?? ''), 'pas_foto');
            });
            if ($photoDoc && $photoDoc->file_path) {
                $raw = $photoDoc->file_path;
            }
        }

        if (! $raw) {
            return null;
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, 'data:image')) {
            return $raw;
        }

        if (str_starts_with($raw, '/storage/')) {
            return $raw;
        }

        if (str_starts_with($raw, 'storage/')) {
            return '/'.$raw;
        }

        if (str_starts_with($raw, '/')) {
            return $raw;
        }

        return '/storage/'.ltrim($raw, '/');
    }

    public function dokumens()
    {
        return $this->hasMany(PendaftarDokumen::class);
    }

    public function tagihans()
    {
        return $this->hasMany(Tagihan::class);
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function kelulusan(): HasOne
    {
        return $this->hasOne(HasilUjian::class, 'pendaftar_id');
    }

    public function keberangkatan(): HasOne
    {
        return $this->hasOne(Keberangkatan::class, 'pendaftar_id');
    }

    public function virtualAccounts(): HasMany
    {
        return $this->hasMany(VirtualAccount::class);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function gelombang(): BelongsTo
    {
        return $this->belongsTo(Gelombang::class);
    }

    public function kelompokUjians(): BelongsToMany
    {
        return $this->belongsToMany(KelompokUjian::class, 'kelompok_ujian_pendaftar', 'pendaftar_id', 'kelompok_ujian_id')
            ->withTimestamps();
    }

    public function penilaians(): HasMany
    {
        return $this->hasMany(Penilaian::class, 'pendaftar_id');
    }

    public function hasilUjian(): HasOne
    {
        return $this->hasOne(HasilUjian::class, 'pendaftar_id');
    }
}
