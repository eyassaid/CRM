<?php

namespace Tests\Feature\Section;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SectionApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_admins_can_list_sections(): void
    {
        $this->actingAsAdmin();
        Section::factory()->count(3)->create();

        $this->getJson('/api/sections')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admins_can_create_a_section(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/sections', ['name' => 'test section'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'test section');

        $this->assertDatabaseHas('sections', ['name' => 'test section']);
    }

    public function test_a_section_requires_a_name(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/sections', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_admins_can_view_a_section(): void
    {
        $this->actingAsAdmin();
        $section = Section::factory()->create();

        $this->getJson('/api/sections/'.$section->id)
            ->assertOk()
            ->assertJsonPath('data.id', $section->id);
    }

    public function test_admins_can_update_a_section(): void
    {
        $this->actingAsAdmin();
        $section = Section::factory()->create();

        $this->putJson('/api/sections/'.$section->id, ['name' => 'renamed section'])
            ->assertOk()
            ->assertJsonPath('data.name', 'renamed section');
    }

    public function test_admins_can_delete_a_section(): void
    {
        $this->actingAsAdmin();
        $section = Section::factory()->create();

        $this->deleteJson('/api/sections/'.$section->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    public function test_employees_and_team_leaders_cannot_use_sections(): void
    {
        $section = Section::factory()->create();

        foreach ([User::factory()->create(), User::factory()->teamLeader()->create()] as $user) {
            Passport::actingAs($user);

            $this->getJson('/api/sections')->assertForbidden();
            $this->postJson('/api/sections', ['name' => 'test section'])->assertForbidden();
            $this->getJson('/api/sections/'.$section->id)->assertForbidden();
            $this->putJson('/api/sections/'.$section->id, ['name' => 'test section'])->assertForbidden();
            $this->deleteJson('/api/sections/'.$section->id)->assertForbidden();
        }

        $this->assertDatabaseHas('sections', ['id' => $section->id, 'name' => $section->name]);
    }

    public function test_guests_cannot_use_sections(): void
    {
        $section = Section::factory()->create();

        $this->getJson('/api/sections')->assertUnauthorized();
        $this->postJson('/api/sections', ['name' => 'test section'])->assertUnauthorized();
        $this->getJson('/api/sections/'.$section->id)->assertUnauthorized();
        $this->putJson('/api/sections/'.$section->id, ['name' => 'test section'])->assertUnauthorized();
        $this->deleteJson('/api/sections/'.$section->id)->assertUnauthorized();
    }
}
