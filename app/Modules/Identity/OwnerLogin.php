<?php

namespace App\Modules\Identity;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class OwnerLogin
{
    private ?string $dummyHash = null;

    public function __construct(
        private readonly Totp $totp,
        private readonly RecoveryCodes $recoveryCodes,
    ) {}

    public function attempt(string $email, string $password, string $code): ?User
    {
        $user = User::query()->where('email', strtolower(trim($email)))->first();

        if ($user === null) {
            // A missing account still pays the Argon2id cost, so the response time stays level.
            $this->dummyHash ??= Hash::make(Str::random(32));
            Hash::check($password, $this->dummyHash);

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        $isOwner = Membership::query()
            ->where('user_id', $user->id)
            ->where('role', Membership::OWNER)
            ->exists();

        $credential = MfaCredential::query()->where('user_id', $user->id)->first();

        if (! $isOwner || $credential === null || $credential->confirmed_at === null) {
            return null;
        }

        if ($this->totp->verify($credential->secret, $code) || $this->recoveryCodes->consume($user, $code)) {
            return $user;
        }

        return null;
    }
}
