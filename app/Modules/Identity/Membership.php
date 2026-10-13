<?php

namespace App\Modules\Identity;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasUuids;

    public const OWNER = 'owner';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
    ];
}
