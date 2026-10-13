<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Identity\Clock;
use App\Modules\Identity\CreateOwner;
use App\Modules\Identity\FrozenClock;
use App\Modules\Identity\Membership;
use App\Modules\Identity\OwnerCredentials;
use App\Modules\Identity\RecoveryCode;
use App\Modules\Identity\Totp;
use App\Modules\Tenancy\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OwnerAccessTest extends TestCase
{
    use RefreshDatabase;

    private const TIMESTAMP = 1_700_000_000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Clock::class, new FrozenClock(self::TIMESTAMP));
    }

    public function test_owner_passwords_use_argon2id(): void
    {
        $hash = Hash::make('correct-horse');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(Hash::check('correct-horse', $hash));
    }

    public function test_the_mfa_secret_is_encrypted_at_rest(): void
    {
        $owner = $this->owner();
        $stored = DB::table('mfa_credentials')->where('user_id', $owner->user->id)->value('secret');

        $this->assertIsString($stored);
        $this->assertStringNotContainsString($owner->secret, $stored);
    }

    public function test_a_studio_has_one_owner(): void
    {
        $owner = $this->owner();
        $second = User::factory()->create();
        $tenantId = (string) Membership::query()->where('user_id', $owner->user->id)->value('tenant_id');

        try {
            Membership::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $second->id,
                'role' => Membership::OWNER,
            ]);

            $this->fail('Expected a unique violation.');
        } catch (QueryException $exception) {
            $this->assertSame('23505', $exception->errorInfo[0]);
        }
    }

    public function test_the_owner_can_sign_in_with_a_current_code(): void
    {
        $owner = $this->confirmedOwner();
        $code = $this->app->make(Totp::class)->currentCode($owner->secret);

        $this->postJson('/owner/login', [
            'email' => 'Sahil@Example.com',
            'password' => 'correct-horse',
            'code' => $code,
        ])->assertNoContent();

        $this->assertAuthenticatedAs($owner->user);
    }

    public function test_unknown_email_and_a_bad_code_return_the_same_response(): void
    {
        $owner = $this->confirmedOwner();

        $unknown = $this->postJson('/owner/login', [
            'email' => 'missing@example.com',
            'password' => 'correct-horse',
            'code' => '000000',
        ]);

        $wrongCode = $this->postJson('/owner/login', [
            'email' => 'sahil@example.com',
            'password' => 'correct-horse',
            'code' => '000000',
        ]);

        $unknown->assertUnprocessable();
        $wrongCode->assertUnprocessable();
        $unknown->assertJson($wrongCode->json());
        $this->assertGuest();
    }

    public function test_a_recovery_code_works_once(): void
    {
        $owner = $this->confirmedOwner();
        $recoveryCode = $owner->recoveryCodes[0];

        $this->postJson('/owner/login', [
            'email' => 'sahil@example.com',
            'password' => 'correct-horse',
            'code' => $recoveryCode,
        ])->assertNoContent();

        $this->postJson('/owner/login', [
            'email' => 'sahil@example.com',
            'password' => 'correct-horse',
            'code' => $recoveryCode,
        ])->assertUnprocessable();

        $this->assertNotNull(RecoveryCode::query()->where('user_id', $owner->user->id)->whereNotNull('used_at')->first());
    }

    public function test_login_attempts_are_limited(): void
    {
        $this->confirmedOwner();
        RateLimiter::clear('sahil@example.com|127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/owner/login', [
                'email' => 'sahil@example.com',
                'password' => 'wrong-password-value',
                'code' => '000000',
            ])->assertUnprocessable();
        }

        $this->postJson('/owner/login', [
            'email' => 'sahil@example.com',
            'password' => 'wrong-password-value',
            'code' => '000000',
        ])->assertTooManyRequests();
    }

    public function test_an_owner_session_ends_after_seven_days(): void
    {
        $owner = $this->confirmedOwner();

        $this->actingAs($owner->user)
            ->withSession(['owner_authenticated_at' => now()->subDays(6)->getTimestamp()])
            ->get('/')
            ->assertOk();

        $this->actingAs($owner->user)
            ->withSession(['owner_authenticated_at' => now()->subDays(8)->getTimestamp()])
            ->get('/')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_owner_create_stops_when_the_studio_is_missing(): void
    {
        $this->artisan('owner:create')->assertFailed();
    }

    private function confirmedOwner(): OwnerCredentials
    {
        $owner = $this->owner();
        $code = $this->app->make(Totp::class)->currentCode($owner->secret);

        $this->assertTrue($this->app->make(CreateOwner::class)->confirm($owner->user, $code));

        return $owner;
    }

    private function owner(): OwnerCredentials
    {
        $studio = Tenant::query()->create([
            'name' => 'Sahil Basumatary',
            'slug' => 'sahil-basumatary',
        ]);

        return $this->app->make(CreateOwner::class)->create(
            $studio->id,
            'Sahil Basumatary',
            'sahil@example.com',
            'correct-horse',
        );
    }
}
