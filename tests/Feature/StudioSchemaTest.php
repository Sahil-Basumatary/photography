<?php

namespace Tests\Feature;

use App\Modules\Crm\Contact;
use App\Modules\Studio\Project;
use App\Modules\Tenancy\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudioSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_one_studio(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, Tenant::query()->count());
        $this->assertDatabaseHas('tenants', [
            'name' => 'Sahil Basumatary',
            'slug' => 'sahil-basumatary',
        ]);
    }

    public function test_a_project_can_use_a_contact_from_the_same_studio(): void
    {
        $studio = $this->studio('sahil-basumatary');
        $contact = Contact::query()->create([
            'tenant_id' => $studio->id,
            'name' => 'King\'s Gala',
        ]);

        $project = Project::query()->create([
            'tenant_id' => $studio->id,
            'contact_id' => $contact->id,
            'title' => 'King\'s Gala',
        ]);

        $this->assertTrue($project->exists);
        $this->assertSame($studio->id, $project->tenant_id);
    }

    public function test_a_project_can_exist_before_a_contact_is_attached(): void
    {
        $studio = $this->studio('sahil-basumatary');

        $project = Project::query()->create([
            'tenant_id' => $studio->id,
            'title' => 'Hardware Hacks',
        ]);

        $this->assertNull($project->contact_id);
    }

    public function test_a_project_cannot_use_a_contact_from_another_studio(): void
    {
        $studio = $this->studio('sahil-basumatary');
        $other = $this->studio('other-studio');
        $contact = Contact::query()->create([
            'tenant_id' => $studio->id,
            'name' => 'Client',
        ]);

        try {
            Project::query()->create([
                'tenant_id' => $other->id,
                'contact_id' => $contact->id,
                'title' => 'Crossed',
            ]);

            $this->fail('A project accepted a contact from another studio.');
        } catch (QueryException $exception) {
            $this->assertSame('23503', $exception->errorInfo[0]);
        }
    }

    private function studio(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
        ]);
    }
}
