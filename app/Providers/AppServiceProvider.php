<?php

namespace App\Providers;

use App\Modules\Identity\Clock;
use App\Modules\Identity\SystemClock;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
    }

    public function boot(): void
    {
        RateLimiter::for('owner-login', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        if (! $this->app->environment('production')) {
            return;
        }

        // RDS presents a certificate. The local Postgres container does not.
        config([
            'database.connections.pgsql.sslmode' => 'verify-full',
            'session.secure' => true,
        ]);
    }
}
