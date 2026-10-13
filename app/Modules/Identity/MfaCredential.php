<?php

namespace App\Modules\Identity;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MfaCredential extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'secret',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'confirmed_at' => 'datetime',
        ];
    }
}
