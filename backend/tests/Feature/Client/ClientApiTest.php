<?php

namespace Tests\Feature\Client;

use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->member = User::factory()->create();
        $this->team->members()->attach($this->member);

        Passport::actingAs($this->member);
    }

    public function test_members_list_the_clients_they_brought_and_the_clients_of_their_projects(): void
    {
        $ownClient = Client::factory()->for($this->team)->for($this->member, 'creator')->create();
        $assignedClient = Client::factory()->for($this->team)->create();
        Project::factory()->for($assignedClient)->hasAttached($this->member, [], 'assignees')->create();
        // A project of the same client they are not on is not counted.
        Project::factory()->for($assignedClient)->create();
        $teammatesClient = Client::factory()->for($this->team)->create();
        $otherClient = Client::factory()->create();

        $ids = $this->getJson('/api/clients')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data.*.id');

        $this->assertEqualsCanonicalizing([$ownClient->id, $assignedClient->id], $ids);
        $this->assertNotContains($teammatesClient->id, $ids);
        $this->assertNotContains($otherClient->id, $ids);
        $this->getJson('/api/clients/'.$assignedClient->id)->assertJsonCount(1, 'data.projects');
    }

    public function test_clients_can_be_searched_by_name_or_phone_number(): void
    {
        $acme = Client::factory()->for($this->team)->for($this->member, 'creator')->create(['name' => 'Acme Corp']);
        $byPhone = Client::factory()->for($this->team)->for($this->member, 'creator')
            ->create(['name' => 'Globex', 'phone_number' => '+15550001234']);
        Client::factory()->for($this->team)->for($this->member, 'creator')->create(['name' => 'Initech']);
        // Not visible to the member, even though it matches.
        Client::factory()->create(['name' => 'Acme Rival']);

        $this->getJson('/api/clients?search=acme')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $acme->id);
        $this->getJson('/api/clients?search=0001234')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $byPhone->id);
        $this->getJson('/api/clients?search=')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/clients?search='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_the_team_leader_lists_every_client_of_the_team(): void
    {
        Passport::actingAs($this->team->leader);
        Client::factory(2)->for($this->team)->create();
        Client::factory()->create();

        $this->getJson('/api/clients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admins_list_every_client(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        Client::factory(2)->create();

        $this->getJson('/api/clients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_the_team_leader_can_view_the_teams_clients(): void
    {
        Passport::actingAs($this->team->leader);
        $client = Client::factory()->for($this->team)->create();

        $this->getJson('/api/clients/'.$client->id)
            ->assertOk()
            ->assertJsonPath('data.id', $client->id);
    }

    public function test_members_can_view_a_client_they_brought(): void
    {
        $client = Client::factory()->for($this->team)->for($this->member, 'creator')->create();

        $this->getJson('/api/clients/'.$client->id)
            ->assertOk()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.team.id', $this->team->id);
    }

    public function test_users_cannot_view_another_teams_client(): void
    {
        $client = Client::factory()->create();

        $this->getJson('/api/clients/'.$client->id)->assertForbidden();
    }

    public function test_members_cannot_view_a_teammates_client_without_a_project_on_it(): void
    {
        $client = Client::factory()->for($this->team)->create();

        $this->getJson('/api/clients/'.$client->id)->assertForbidden();
    }

    public function test_members_assigned_to_a_project_view_the_client_but_cannot_change_it(): void
    {
        $client = Client::factory()->for($this->team)->create();
        Project::factory()->for($client)->hasAttached($this->member, [], 'assignees')->create();

        $this->getJson('/api/clients/'.$client->id)->assertOk();
        $this->putJson('/api/clients/'.$client->id, ['name' => 'Renamed'])->assertForbidden();
        $this->deleteJson('/api/clients/'.$client->id)->assertForbidden();
    }

    public function test_members_can_add_a_client_to_their_team(): void
    {
        $clientData = [
            'name' => 'Acme Inc.',
            'phone_number' => '+15551234567',
        ];

        $this->postJson('/api/clients', [...$clientData, 'team_id' => $this->team->id])
            ->assertCreated()
            ->assertJsonFragment($clientData)
            ->assertJsonPath('data.team_id', $this->team->id)
            ->assertJsonPath('data.created_by', $this->member->id);

        $this->assertDatabaseHas('clients', [
            ...$clientData,
            'team_id' => $this->team->id,
            'created_by' => $this->member->id,
        ]);
    }

    public function test_members_cannot_add_a_client_to_another_team(): void
    {
        $otherTeam = Team::factory()->create();

        $this->postJson('/api/clients', [
            'name' => 'Acme Inc.',
            'phone_number' => '+15551234567',
            'team_id' => $otherTeam->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('team_id');

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_a_client_requires_a_team(): void
    {
        $this->postJson('/api/clients', [
            'name' => 'Acme Inc.',
            'phone_number' => '+15551234567',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('team_id');
    }

    public function test_admins_can_add_a_client_to_any_team(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/clients', [
            'name' => 'Acme Inc.',
            'phone_number' => '+15551234567',
            'team_id' => $this->team->id,
        ])->assertCreated();
    }

    public function test_members_can_update_a_client_they_brought(): void
    {
        $client = Client::factory()->for($this->team)->for($this->member, 'creator')->create();
        $clientData = [
            'name' => 'Acme Corporation',
            'phone_number' => '+15557654321',
        ];

        $this->putJson('/api/clients/'.$client->id, $clientData)
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme Corporation');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            ...$clientData,
        ]);
    }

    public function test_users_cannot_update_another_teams_client(): void
    {
        $client = Client::factory()->create();

        $this->putJson('/api/clients/'.$client->id, ['name' => 'Taken over'])->assertForbidden();
    }

    public function test_members_can_delete_a_client_they_brought(): void
    {
        $client = Client::factory()->for($this->team)->for($this->member, 'creator')->create();

        $this->deleteJson('/api/clients/'.$client->id)->assertNoContent();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_users_cannot_delete_another_teams_client(): void
    {
        $client = Client::factory()->create();

        $this->deleteJson('/api/clients/'.$client->id)->assertForbidden();

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }
}
