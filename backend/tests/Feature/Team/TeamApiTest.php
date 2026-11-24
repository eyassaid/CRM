<?php

namespace Tests\Feature\Team;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Section;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_team_saves_its_members(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $section = Section::factory()->create();
        $leader = User::factory()->teamLeader()->create();
        $members = User::factory(2)->create();

        $response = $this->postJson('/api/sections/'.$section->id.'/teams', [
            'name' => 'Sales',
            'section_id' => $section->id,
            'leader_id' => $leader->id,
            'members_ids' => $members->pluck('id')->all(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.leader_name', $leader->name)
            ->assertJsonCount(2, 'data.members');

        $this->assertEqualsCanonicalizing(
            $members->pluck('id')->all(),
            Team::find($response->json('data.id'))->members()->pluck('users.id')->all(),
        );
    }

    public function test_updating_a_team_replaces_its_members(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $team = Team::factory()->create();
        $team->members()->attach(User::factory(2)->create());
        $newMember = User::factory()->create();

        $this->putJson('/api/teams/'.$team->id, [
            'name' => $team->name,
            'section_id' => $team->section_id,
            'leader_id' => $team->leader_id,
            'members_ids' => [$newMember->id],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.members')
            ->assertJsonPath('data.members.0.id', $newMember->id);
    }

    public function test_employees_and_team_leaders_cannot_create_or_change_teams(): void
    {
        $section = Section::factory()->create();
        $team = Team::factory()->for($section)->create();
        $member = User::factory()->create();
        $team->members()->attach($member);
        $payload = [
            'name' => 'Sales',
            'section_id' => $section->id,
            'leader_id' => $team->leader_id,
        ];

        foreach ([$member, $team->leader] as $user) {
            Passport::actingAs($user);

            $this->getJson('/api/sections/'.$section->id.'/teams')->assertForbidden();
            $this->postJson('/api/sections/'.$section->id.'/teams', $payload)->assertForbidden();
            $this->putJson('/api/teams/'.$team->id, $payload)->assertForbidden();
            $this->deleteJson('/api/teams/'.$team->id)->assertForbidden();
        }

        $this->assertDatabaseCount('teams', 1);
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => $team->name]);
    }

    public function test_the_teams_people_can_view_it(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();
        $team->members()->attach($member);

        foreach ([$member, $team->leader] as $user) {
            Passport::actingAs($user);

            $this->getJson('/api/teams/'.$team->id)
                ->assertOk()
                ->assertJsonPath('data.id', $team->id);
        }
    }

    public function test_users_cannot_view_a_team_they_are_not_in(): void
    {
        $team = Team::factory()->create();
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/teams/'.$team->id)->assertForbidden();
    }

    public function test_users_get_the_teams_they_belong_to(): void
    {
        $member = User::factory()->create();
        $memberOf = Team::factory()->create();
        $memberOf->members()->attach($member);
        $leaderOf = Team::factory()->create(['leader_id' => $member->id]);
        Team::factory()->create();
        Passport::actingAs($member);

        $ids = $this->getJson('/api/me/teams')->assertOk()->json('data.*.id');

        $this->assertEqualsCanonicalizing([$memberOf->id, $leaderOf->id], $ids);
    }

    public function test_admins_get_every_team(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        Team::factory(3)->create();

        $this->getJson('/api/me/teams')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_the_leader_sees_how_many_clients_each_member_brought(): void
    {
        $team = Team::factory()->create();
        [$busy, $quiet] = User::factory(2)->create();
        $team->members()->attach([$busy->id, $quiet->id]);
        Client::factory(3)->for($team)->for($busy, 'creator')->create();
        Client::factory()->for($team)->for($team->leader, 'creator')->create();
        // Clients the member brought to another team are not counted here.
        Client::factory()->for($quiet, 'creator')->create();
        Passport::actingAs($team->leader);

        $this->getJson('/api/reports/teams/'.$team->id)
            ->assertOk()
            ->assertJsonPath('data.clients_count', 4)
            ->assertJsonPath('data.leader.clients_count', 1)
            ->assertJsonPath('data.members.0.id', $busy->id)
            ->assertJsonPath('data.members.0.clients_count', 3)
            ->assertJsonPath('data.members.1.id', $quiet->id)
            ->assertJsonPath('data.members.1.clients_count', 0);
    }

    public function test_the_team_report_lists_the_team_clients_with_their_projects(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $team = Team::factory()->create();
        $client = Client::factory()->for($team)->create(['name' => 'Acme']);
        $project = Project::factory()->for($client)->create(['status' => ProjectStatus::Done]);
        // Clients of other teams are not listed.
        Client::factory()->create();

        $this->getJson('/api/reports/teams/'.$team->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.clients')
            ->assertJsonPath('data.clients.0.id', $client->id)
            ->assertJsonPath('data.clients.0.name', 'Acme')
            ->assertJsonCount(1, 'data.clients.0.projects')
            ->assertJsonPath('data.clients.0.projects.0.id', $project->id)
            ->assertJsonPath('data.clients.0.projects.0.status', ProjectStatus::Done->value);
    }

    public function test_members_cannot_see_the_team_report(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();
        $team->members()->attach($member);
        Passport::actingAs($member);

        $this->getJson('/api/reports/teams/'.$team->id)->assertForbidden();
    }

    public function test_admins_see_clients_and_projects_per_team(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $team = Team::factory()->create();
        $client = Client::factory()->for($team)->create();
        Client::factory()->for($team)->create();
        Project::factory()->for($client)->create(['status' => ProjectStatus::Done]);
        Project::factory(2)->for($client)->create(['status' => ProjectStatus::WorkingOnIt]);
        Team::factory()->create();

        $this->getJson('/api/reports/teams')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $team->id)
            ->assertJsonPath('data.0.clients_count', 2)
            ->assertJsonPath('data.0.projects_count', 3)
            ->assertJsonPath('data.0.projects_by_status', [
                'not_started' => 0,
                'working_on_it' => 2,
                'cancelled' => 0,
                'done' => 1,
            ])
            ->assertJsonPath('data.1.clients_count', 0);
    }

    public function test_only_admins_see_the_teams_overview(): void
    {
        $team = Team::factory()->create();
        Passport::actingAs($team->leader);

        $this->getJson('/api/reports/teams')->assertForbidden();
    }

    public function test_only_team_leaders_can_lead_a_team(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $section = Section::factory()->create();

        $this->postJson('/api/sections/'.$section->id.'/teams', [
            'name' => 'Sales',
            'section_id' => $section->id,
            'leader_id' => User::factory()->create()->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('leader_id');
    }

    public function test_a_team_with_members_cannot_be_deleted(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $team = Team::factory()->create();
        $team->members()->attach(User::factory()->create());

        $this->deleteJson('/api/teams/'.$team->id)
            ->assertStatus(409)
            ->assertJsonPath('message', "Remove the team's members before deleting it.");

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }

    public function test_a_team_without_members_can_be_deleted_and_its_projects_lose_their_people(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));
        $team = Team::factory()->create();
        $client = Client::factory()->for($team)->create();
        $project = Project::factory()->for($client)->create();
        $project->assignees()->attach($team->leader_id);

        $this->deleteJson('/api/teams/'.$team->id)->assertNoContent();

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'team_id' => null]);
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id]);
    }
}
