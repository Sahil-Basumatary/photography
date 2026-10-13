<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function test_the_public_entry_responds(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_the_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_postgres_accepts_a_query(): void
    {
        $row = DB::select('select 1 as ok');

        $this->assertSame(1, $row[0]->ok);
    }
}
