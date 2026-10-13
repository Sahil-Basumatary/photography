<?php

namespace App\Modules\Identity;

final class FrozenClock implements Clock
{
    public function __construct(private int $timestamp) {}

    public function now(): int
    {
        return $this->timestamp;
    }
}
