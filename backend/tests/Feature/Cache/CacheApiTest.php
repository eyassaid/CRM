<?php

namespace Tests\Feature\Cache;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CacheApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        Passport::actingAs($this->admin);
    }

    public function test_repeated_get_requests_are_served_from_the_cache(): void
    {
        Section::factory()->create();

        $this->getJson('/api/sections')->assertOk()->assertHeader('X-Cache', 'MISS')->assertJsonCount(1, 'data');

        // Written behind the API's back: the cached page does not see it.
        Section::factory()->create();

        $this->getJson('/api/sections')->assertOk()->assertHeader('X-Cache', 'HIT')->assertJsonCount(1, 'data');
    }

    public function test_a_successful_write_clears_the_cache(): void
    {
        $this->getJson('/api/sections')->assertOk()->assertJsonCount(0, 'data');

        $this->postJson('/api/sections', ['name' => 'Sales'])->assertCreated();

        $this->getJson('/api/sections')->assertOk()->assertHeader('X-Cache', 'MISS')->assertJsonCount(1, 'data');
    }

    public function test_a_failed_write_keeps_the_cache(): void
    {
        $this->getJson('/api/sections')->assertOk();

        $this->postJson('/api/sections', ['name' => ''])->assertUnprocessable();

        $this->getJson('/api/sections')->assertHeader('X-Cache', 'HIT');
    }

    public function test_each_user_gets_their_own_cached_responses(): void
    {
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $this->admin->id);

        $other = User::factory()->create(['role' => 'admin']);
        Passport::actingAs($other);

        $this->getJson('/api/me')->assertHeader('X-Cache', 'MISS')->assertJsonPath('data.id', $other->id);
    }

    public function test_cached_admin_pages_still_require_admin_rights(): void
    {
        $this->getJson('/api/sections')->assertOk();

        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/sections')->assertForbidden();
    }

    public function test_error_responses_are_not_cached(): void
    {
        $this->getJson('/api/sections/999')->assertNotFound();

        $section = Section::factory()->create(['id' => 999]);

        $this->getJson('/api/sections/'.$section->id)->assertOk();
    }
}
