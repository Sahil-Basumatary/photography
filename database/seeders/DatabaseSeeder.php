<?php

namespace Database\Seeders;

use App\Modules\Tenancy\Tenant;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->firstOrCreate(
            ['slug' => 'sahil-basumatary'],
            ['name' => 'Sahil Basumatary'],
        );
    }
}
