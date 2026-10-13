<?php

namespace Tests\Unit;

use App\Modules\Identity\Password;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PasswordTest extends TestCase
{
    public function test_an_owner_password_needs_twelve_characters(): void
    {
        $password = new Password('correct-horse');

        $this->assertSame('correct-horse', $password->value);

        $this->expectException(InvalidArgumentException::class);

        new Password('short-pass');
    }
}
