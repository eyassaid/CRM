<?php

namespace Tests\Feature\User;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
    }

    public function test_users_api_requires_authentication(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_users_can_log_in_and_out_via_the_api(): void
    {
        $user = User::factory()->teamLeader()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'team_leader')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['access_token']]);

        Passport::actingAs($user);

        $this->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Successfully logged out.');
    }

    public function test_users_cannot_log_in_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_me_returns_the_current_user(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'employee');
    }

    public function test_employees_cannot_manage_users(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        Team::factory()->create()->members()->attach($employee);
        Passport::actingAs($employee);

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [
            'name' => 'New Employee',
            'email' => 'new@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
    }

    public function test_admin_users_can_manage_users(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $existingUser = User::factory()->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $existingUser->id])
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson('/api/users/'.$existingUser->id)
            ->assertOk()
            ->assertJsonPath('data.id', $existingUser->id);

        $createdUser = $this->postJson('/api/users', [
            'name' => 'API User',
            'email' => 'api.user@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.role', 'employee')
            ->json('data');

        $this->putJson('/api/users/'.$createdUser['id'], [
            'name' => 'Updated API User',
            'email' => 'api.user@example.test',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated API User');

        $this->deleteJson('/api/users/'.$createdUser['id'])->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $createdUser['id']]);
    }

    public function test_admins_can_add_a_new_user_to_any_team(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $team = Team::factory()->create();

        $id = $this->postJson('/api/users', [
            'name' => 'Team User',
            'email' => 'team.user@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => $team->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.teams.0.id', $team->id)
            ->assertJsonPath('data.teams.0.section_name', $team->section->name)
            ->json('data.id');

        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $id]);
    }

    public function test_team_leaders_add_employees_to_the_teams_they_lead(): void
    {
        $team = Team::factory()->create();
        Passport::actingAs($team->leader);

        $response = $this->postJson('/api/users', [
            'name' => 'New Employee',
            'email' => 'new@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => $team->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.role', 'employee');

        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $response->json('data.id')]);
    }

    public function test_team_leaders_must_pick_a_team_they_lead(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        Passport::actingAs($team->leader);
        $payload = [
            'name' => 'New Employee',
            'email' => 'new@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/users', $payload)->assertUnprocessable()->assertJsonValidationErrors('team_id');
        $this->postJson('/api/users', [...$payload, 'team_id' => $otherTeam->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('team_id');

        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_team_leaders_list_only_their_team_members_and_cannot_edit_users(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();
        $team->members()->attach($member);
        $outsider = User::factory()->create();
        Passport::actingAs($team->leader);

        $ids = $this->getJson('/api/users')->assertOk()->json('data.*.id');
        $this->assertSame([$member->id], $ids);

        $this->getJson('/api/users/'.$member->id)->assertForbidden();
        $this->putJson('/api/users/'.$member->id, ['name' => 'Renamed', 'email' => $member->email])
            ->assertForbidden();
        $this->deleteJson('/api/users/'.$outsider->id)->assertForbidden();
    }

    public function test_users_can_be_searched_by_name_or_email(): void
    {
        $team = Team::factory()->create();
        $jane = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.test']);
        $byEmail = User::factory()->create(['name' => 'Sam Smith', 'email' => 'sam.acme@example.test']);
        $team->members()->attach([$jane->id, $byEmail->id]);
        // Not on the leader's team, even though it matches.
        User::factory()->create(['name' => 'Jane Outsider']);
        Passport::actingAs($team->leader);

        $this->getJson('/api/users?search=jane')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $jane->id);
        $this->getJson('/api/users?search=acme')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $byEmail->id);
        $this->getJson('/api/users?search='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_admins_can_create_and_promote_team_leaders(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/users', [
            'name' => 'New Leader',
            'email' => 'leader@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'team_leader',
        ])
            ->assertCreated()
            ->assertJsonPath('data.role', 'team_leader');

        $employee = User::factory()->create();

        $this->putJson('/api/users/'.$employee->id, [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => 'team_leader',
        ])
            ->assertOk()
            ->assertJsonPath('data.role', 'team_leader');

        $this->postJson('/api/users', [
            'name' => 'New Admin',
            'email' => 'admin2@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_a_user_who_leads_a_team_keeps_the_team_leader_role(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $team = Team::factory()->create();

        $this->putJson('/api/users/'.$team->leader_id, [
            'name' => $team->leader->name,
            'email' => $team->leader->email,
            'role' => 'employee',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_team_leaders_cannot_choose_a_role(): void
    {
        $team = Team::factory()->create();
        Passport::actingAs($team->leader);

        $this->postJson('/api/users', [
            'name' => 'New Leader',
            'email' => 'leader@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => $team->id,
            'role' => 'team_leader',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }
}
