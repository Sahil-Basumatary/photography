<?php

namespace App\Modules\Identity;

use InvalidArgumentException;

final class Password
{
    public function __construct(public readonly string $value)
    {
        if (mb_strlen($this->value) < 12) {
            throw new InvalidArgumentException('Use at least 12 characters.');
        }
    }
}
