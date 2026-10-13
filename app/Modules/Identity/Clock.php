<?php

namespace App\Modules\Identity;

interface Clock
{
    public function now(): int;
}
