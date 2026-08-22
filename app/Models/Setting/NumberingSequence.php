<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NumberingSequence extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'prefix',
        'pattern',
        'padding',
        'next_number',
    ];
}
