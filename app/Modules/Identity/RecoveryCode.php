<?php

namespace App\Modules\Identity;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecoveryCode extends Model
{
    use HasUuids;

    protected $table = 'mfa_recovery_codes';

    protected $fillable = [
        'user_id',
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
