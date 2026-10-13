<?php

namespace App\Modules\Identity;

final class SystemClock implements Clock
{
    public function now(): int
    {
        return time();
    }
}
