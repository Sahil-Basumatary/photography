<?php

namespace App\Modules\Identity;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateOwner
{
    public function __construct(
        private readonly Totp $totp,
        private readonly RecoveryCodes $recoveryCodes,
    ) {}

    public function create(string $tenantId, string $name, string $email, string $password): OwnerCredentials
    {
        $password = new Password($password);
        $email = strtolower(trim($email));

        return DB::transaction(function () use ($tenantId, $name, $email, $password): OwnerCredentials {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password->value,
            ]);

            Membership::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'role' => Membership::OWNER,
            ]);

            $secret = $this->totp->generateSecret();

            MfaCredential::query()->create([
                'user_id' => $user->id,
                'secret' => $secret,
            ]);

            return new OwnerCredentials(
                $user,
                $secret,
                $this->totp->provisioningUri($email, $secret),
                $this->recoveryCodes->issue($user),
            );
        });
    }

    public function confirm(User $user, string $code): bool
    {
        $credential = MfaCredential::query()->where('user_id', $user->id)->first();

        if ($credential === null || $credential->confirmed_at !== null) {
            return false;
        }

        if (! $this->totp->verify($credential->secret, $code)) {
            return false;
        }

        $credential->forceFill(['confirmed_at' => now()])->save();

        return true;
    }
}
