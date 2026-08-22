<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasUuids;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];
}
