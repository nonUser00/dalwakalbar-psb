<?php

namespace App\Models\Auth;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Ujian\KelompokUjian;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'foto', 'name', 'email', 'password', 'gender', 'tempat_lahir', 'tanggal_lahir',
    'nip', 'nik', 'no_kk', 'no_akta_lahir', 'nomor_hp', 'alamat_lengkap', 'rt', 'rw',
    'kode_pos', 'provinsi', 'kabupaten_kota', 'kecamatan', 'kelurahan_desa', 'is_active',
    'allowed_gender', 'allowed_cabang_ids', 'allowed_jenjang_ids',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, \Spatie\Permission\Traits\HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $appends = ['foto_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allowed_cabang_ids' => 'array',
            'allowed_jenjang_ids' => 'array',
        ];
    }

    public function getFotoUrlAttribute()
    {
        return $this->foto ? asset('storage/'.$this->foto) : null;
    }

    public function kelompok_ujians()
    {
        return $this->belongsToMany(KelompokUjian::class, 'kelompok_ujian_penguji', 'user_id', 'kelompok_ujian_id')
            ->withTimestamps();
    }

    public function kelompok_ujians_sebagai_koordinator()
    {
        return $this->belongsToMany(KelompokUjian::class, 'kelompok_ujian_koordinator', 'user_id', 'kelompok_ujian_id')
            ->withTimestamps();
    }

    public function kelompok_ujians_sebagai_pengawas()
    {
        return $this->kelompok_ujians_sebagai_koordinator();
    }

    public function penilaians()
    {
        return $this->hasMany(Penilaian::class, 'penguji_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'provinsi', 'code');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'kabupaten_kota', 'code');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'kecamatan', 'code');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'kelurahan_desa', 'code');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function allowedCabangIds(): array
    {
        return is_array($this->allowed_cabang_ids) ? $this->allowed_cabang_ids : [];
    }

    public function allowedJenjangIds(): array
    {
        return is_array($this->allowed_jenjang_ids) ? $this->allowed_jenjang_ids : [];
    }

    public function allowedGender(): string
    {
        return $this->allowed_gender ?: 'ALL';
    }

    /**
     * Scope query pendaftar berdasarkan izin manajemen data user pegawai.
     */
    public function scopeFilterPendaftarByPermissions($query, ?User $user = null)
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return $query;
        }

        // 1. Filter Cabang
        $allowedCabang = $user->allowed_cabang_ids;
        if (is_array($allowedCabang)) {
            if (empty($allowedCabang)) {
                return $query->whereRaw('1 = 0');
            }
            $query->whereIn('cabang_id', $allowedCabang);
        }

        // 2. Filter Jenjang
        $allowedJenjang = $user->allowed_jenjang_ids;
        if (is_array($allowedJenjang)) {
            if (empty($allowedJenjang)) {
                return $query->whereRaw('1 = 0');
            }
            $query->whereIn('jenjang_id', $allowedJenjang);
        }

        // 3. Filter Gender
        if ($user->allowed_gender && $user->allowed_gender !== 'ALL') {
            $g = strtolower($user->allowed_gender);
            $query->where(function ($q) use ($g) {
                if (in_array($g, ['l', 'laki-laki'])) {
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(personal_data, '$.jenis_kelamin'))) IN ('l', 'laki-laki', 'laki')")
                        ->orWhereNull('personal_data'); // Opsional: tampilkan draft yang belum isi gender
                } elseif (in_array($g, ['p', 'perempuan'])) {
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(personal_data, '$.jenis_kelamin'))) IN ('p', 'perempuan', 'putri')")
                        ->orWhereNull('personal_data'); // Opsional: tampilkan draft yang belum isi gender
                }
            });
        }

        return $query;
    }
}
