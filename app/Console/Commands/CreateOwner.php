<?php

namespace App\Console\Commands;

use App\Modules\Identity\CreateOwner as CreateOwnerAccount;
use App\Modules\Identity\Membership;
use App\Modules\Tenancy\Tenant;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateOwner extends Command
{
    protected $signature = 'owner:create';

    protected $description = 'Create the studio owner and enroll multi-factor authentication';

    public function handle(CreateOwnerAccount $createOwner): int
    {
        $tenant = Tenant::query()->where('slug', 'sahil-basumatary')->first();

        if ($tenant === null) {
            $this->error('Seed the studio before creating the owner.');

            return self::FAILURE;
        }

        if (Membership::query()->where('tenant_id', $tenant->id)->where('role', Membership::OWNER)->exists()) {
            $this->error('This studio already has an owner.');

            return self::FAILURE;
        }

        $name = (string) $this->ask('Name');
        $email = (string) $this->ask('Email');
        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->error('The passwords differ.');

            return self::FAILURE;
        }

        try {
            $credentials = $createOwner->create($tenant->id, $name, $email, $password);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line($credentials->uri);
        $code = (string) $this->ask('Authentication code');

        if (! $createOwner->confirm($credentials->user, $code)) {
            $credentials->user->delete();
            $this->error('The code did not match.');

            return self::FAILURE;
        }

        foreach ($credentials->recoveryCodes as $recoveryCode) {
            $this->line($recoveryCode);
        }

        return self::SUCCESS;
    }
}
