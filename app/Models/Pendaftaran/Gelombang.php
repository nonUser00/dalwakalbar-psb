<?php

namespace App\Models\Pendaftaran;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Gelombang extends Model
{
    use HasUuids;

    protected $fillable = [
        'periode_id',
        'name',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }
}
