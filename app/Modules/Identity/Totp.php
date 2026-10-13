<?php

namespace App\Modules\Identity;

use OTPHP\TOTP as Otp;

final class Totp
{
    private const LEEWAY_SECONDS = 29;

    public function __construct(private readonly Clock $clock) {}

    public function generateSecret(): string
    {
        return Otp::generate()->getSecret();
    }

    public function provisioningUri(string $email, string $secret): string
    {
        $otp = $this->otp($secret);
        $otp->setLabel($email);
        $otp->setIssuer('Sahil Basumatary');

        return $otp->getProvisioningUri();
    }

    public function currentCode(string $secret): string
    {
        return $this->otp($secret)->at($this->clock->now());
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->otp($secret)->verify($code, $this->clock->now(), self::LEEWAY_SECONDS);
    }

    private function otp(string $secret): Otp
    {
        return Otp::createFromSecret($secret);
    }
}
