<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';
}
