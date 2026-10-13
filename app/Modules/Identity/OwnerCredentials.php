<?php

namespace App\Modules\Identity;

use App\Models\User;

final class OwnerCredentials
{
    /**
     * @param  list<string>  $recoveryCodes
     */
    public function __construct(
        public readonly User $user,
        public readonly string $secret,
        public readonly string $uri,
        public readonly array $recoveryCodes,
    ) {}
}
