<?php

namespace App\Modules\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
    ];
}
