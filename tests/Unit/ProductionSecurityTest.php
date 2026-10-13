<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    public function test_production_requires_verified_database_tls_and_secure_cookies(): void
    {
        $environment = $this->app['env'];
        $sslmode = config('database.connections.pgsql.sslmode');
        $secure = config('session.secure');

        try {
            $this->app['env'] = 'production';

            (new AppServiceProvider($this->app))->boot();

            $this->assertSame('verify-full', config('database.connections.pgsql.sslmode'));
            $this->assertTrue(config('session.secure'));
        } finally {
            $this->app['env'] = $environment;
            config([
                'database.connections.pgsql.sslmode' => $sslmode,
                'session.secure' => $secure,
            ]);
        }
    }
}
