<?php

namespace App\Modules\Studio;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'title',
    ];
}
