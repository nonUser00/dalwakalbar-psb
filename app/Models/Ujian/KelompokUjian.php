<?php

namespace App\Models\Ujian;

use App\Enums\StatusKelompokUjian;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KelompokUjian extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama_kelompok', 'tanggal_ujian',
        'waktu_mulai', 'waktu_selesai', 'lokasi', 'status',
    ];

    protected $casts = [
        'status' => StatusKelompokUjian::class,
        'tanggal_ujian' => 'date',
    ];

    public function pengujis(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelompok_ujian_penguji', 'kelompok_ujian_id', 'user_id')
            ->withPivot('peran')
            ->withTimestamps();
    }

    public function pewawancara(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelompok_ujian_penguji', 'kelompok_ujian_id', 'user_id')
            ->wherePivot('peran', 'interview')
            ->withPivot('peran')
            ->withTimestamps();
    }

    public function pengujiMembaca(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelompok_ujian_penguji', 'kelompok_ujian_id', 'user_id')
            ->wherePivot('peran', 'tes_membaca')
            ->withPivot('peran')
            ->withTimestamps();
    }

    public function pengujiMenulis(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelompok_ujian_penguji', 'kelompok_ujian_id', 'user_id')
            ->wherePivot('peran', 'tes_menulis')
            ->withPivot('peran')
            ->withTimestamps();
    }

    public function pengujiHafalan(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelompok_ujian_penguji', 'kelompok_ujian_id', 'user_id')
            ->wherePivot('peran', 'tes_hafalan')
            ->withPivot('peran')
            ->withTimestamps();
    }

    public function koordinator(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelompok_ujian_koordinator', 'kelompok_ujian_id', 'user_id')
            ->withTimestamps();
    }

    public function pengawas(): BelongsToMany
    {
        return $this->koordinator();
    }

    public function pendaftars(): BelongsToMany
    {
        return $this->belongsToMany(Pendaftar::class, 'kelompok_ujian_pendaftar', 'kelompok_ujian_id', 'pendaftar_id')
            ->withTimestamps();
    }

    public function penilaians(): HasMany
    {
        return $this->hasMany(Penilaian::class, 'kelompok_ujian_id');
    }
}
