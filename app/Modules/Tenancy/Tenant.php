<?php

namespace App\Modules\Tenancy;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
    ];
}
