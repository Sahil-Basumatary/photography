<?php

namespace App\Modules;

use App\Modules\Audit\Audit;
use App\Modules\Billing\Billing;
use App\Modules\Contracts\Contracts;
use App\Modules\Crm\Crm;
use App\Modules\Galleries\Galleries;
use App\Modules\Identity\Identity;
use App\Modules\Media\Media;
use App\Modules\Notifications\Notifications;
use App\Modules\Studio\Studio;
use App\Modules\Tenancy\Tenancy;

final class Catalog
{
    /**
     * @return list<class-string>
     */
    public static function classes(): array
    {
        return [
            Tenancy::class,
            Identity::class,
            Crm::class,
            Studio::class,
            Contracts::class,
            Billing::class,
            Galleries::class,
            Media::class,
            Notifications::class,
            Audit::class,
        ];
    }
}
