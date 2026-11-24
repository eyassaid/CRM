<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ProjectAssigned;
use App\Notifications\ProjectCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Team $team;

    private User $member;

    // A client another member of the team brought.
    private Client $teammatesClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->member = User::factory()->create();
        $this->team->members()->attach($this->member);
        $this->client = Client::factory()->for($this->team)->for($this->member, 'creator')->create();
        $this->teammatesClient = Client::factory()->for($this->team)->create();

        Passport::actingAs($this->member);
        Storage::fake('public');
        Notification::fake();
    }

    public function test_the_owner_lists_every_project_of_their_client(): void
    {
        Project::factory(2)->for($this->client)->create();

        $this->getJson('/api/clients/'.$this->client->id.'/projects/')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_helpers_list_only_the_projects_they_help_on(): void
    {
        $helping = $this->helperProject();
        $notHelping = Project::factory()->for($this->teammatesClient)->create();

        $ids = $this->getJson('/api/clients/'.$this->teammatesClient->id.'/projects/')
            ->assertOk()
            ->json('data.*.id');

        $this->assertSame([$helping->id], $ids);
        $this->assertNotContains($notHelping->id, $ids);
    }

    public function test_the_team_leader_lists_every_project_of_the_client(): void
    {
        $this->helperProject();
        Project::factory()->for($this->teammatesClient)->create();
        Passport::actingAs($this->team->leader);

        $this->getJson('/api/clients/'.$this->teammatesClient->id.'/projects/')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_project_without_an_image_returns_a_null_image(): void
    {
        $project = Project::factory()->for($this->client)->create(['image' => null]);

        $this->getJson('/api/projects/'.$project->id)
            ->assertOk()
            ->assertJsonPath('data.image', null);
    }

    public function test_the_owner_can_view_a_project_of_their_client(): void
    {
        $project = Project::factory()->for($this->client)->create();

        $this->getJson('/api/projects/'.$project->id)
            ->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.client.id', $this->client->id);
    }

    public function test_helpers_can_view_and_edit_a_project_they_help_on(): void
    {
        $project = $this->helperProject(['status' => ProjectStatus::WorkingOnIt]);

        $this->getJson('/api/projects/'.$project->id)->assertOk();
        $this->putJson('/api/projects/'.$project->id, ['status' => 'done', 'items' => $this->items()])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');
    }

    public function test_members_cannot_see_or_change_a_teammates_project_they_do_not_help_on(): void
    {
        $project = Project::factory()->for($this->teammatesClient)->create();

        $this->getJson('/api/projects/'.$project->id)->assertForbidden();
        $this->putJson('/api/projects/'.$project->id, ['name' => 'Renamed'])->assertForbidden();
        $this->deleteJson('/api/projects/'.$project->id)->assertForbidden();
    }

    public function test_a_project_the_owner_creates_is_a_request_waiting_for_approval(): void
    {
        $response = $this->postJson('/api/clients/'.$this->client->id.'/projects/', [
            'name' => 'Website redesign',
            'status' => 'working_on_it',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Website redesign')
            ->assertJsonPath('data.status', 'working_on_it')
            ->assertJsonPath('data.approved_at', null)
            ->assertJsonPath('data.client.id', $this->client->id)
            ->assertJsonCount(0, 'data.assignees');

        $this->assertDatabaseHas('projects', ['id' => $response->json('data.id'), 'approved_at' => null]);
    }

    public function test_helpers_cannot_create_projects_for_a_client_they_do_not_own(): void
    {
        $this->helperProject();

        $this->postJson('/api/clients/'.$this->teammatesClient->id.'/projects/', ['name' => 'Sneaky'])
            ->assertForbidden();
    }

    public function test_a_project_the_team_leader_creates_is_approved(): void
    {
        Passport::actingAs($this->team->leader);

        $this->postJson('/api/clients/'.$this->client->id.'/projects/', ['name' => 'Website redesign'])
            ->assertCreated()
            ->assertJsonCount(0, 'data.assignees');

        $this->assertNotNull(Project::firstWhere('name', 'Website redesign')->approved_at);
    }

    public function test_the_team_leader_approves_a_requested_project(): void
    {
        $project = Project::factory()->pending()->for($this->client)->create();
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$project->id, ['approved' => true])->assertOk();

        $this->assertNotNull($project->refresh()->approved_at);
    }

    public function test_members_cannot_approve_their_own_request(): void
    {
        $project = Project::factory()->pending()->for($this->client)->create();

        $this->putJson('/api/projects/'.$project->id, ['approved' => true])->assertForbidden();
        $this->postJson('/api/clients/'.$this->client->id.'/projects', ['name' => 'Sneaky', 'approved' => true])
            ->assertForbidden();

        $this->assertNull($project->refresh()->approved_at);
    }

    public function test_a_new_project_is_not_started_by_default(): void
    {
        $this->postJson('/api/clients/'.$this->client->id.'/projects/', ['name' => 'Website redesign'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'not_started');
    }

    public function test_the_project_status_must_be_one_of_the_four_stages(): void
    {
        $this->postJson('/api/clients/'.$this->client->id.'/projects/', [
            'name' => 'Website redesign',
            'status' => 'paused',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_the_owner_can_update_a_project_of_their_client(): void
    {
        $project = Project::factory()->for($this->client)->create(['status' => ProjectStatus::WorkingOnIt]);

        $this->putJson('/api/projects/'.$project->id, [
            'name' => 'Website refresh',
            'status' => 'done',
            'items' => $this->items(),
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Website refresh')
            ->assertJsonPath('data.status', 'done');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'done']);
    }

    public function test_marking_a_project_done_creates_its_invoice(): void
    {
        $project = Project::factory()->for($this->client)->create(['status' => ProjectStatus::WorkingOnIt]);

        $this->putJson('/api/projects/'.$project->id, [
            'status' => 'done',
            'items' => [
                ['name' => 'Design', 'cost' => 1200.5],
                ['name' => 'Hosting', 'cost' => '99.50'],
            ],
        ])->assertOk();

        $invoice = $project->invoices()->sole();
        $this->assertSame([
            ['name' => 'Design', 'cost' => 1200.5],
            ['name' => 'Hosting', 'cost' => 99.5],
        ], $invoice->items);
        $this->assertSame(1300.0, $invoice->total());
        $this->assertSame($this->member->id, $invoice->created_by);
        // The PDF is not stored; it is rendered on download.
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertStringStartsWith('%PDF', $this->get('/api/invoices/'.$invoice->id.'/pdf')->assertOk()->getContent());

        $this->getJson('/api/projects/'.$project->id.'/invoices')
            ->assertOk()
            ->assertJsonPath('data.0.items.1.name', 'Hosting')
            ->assertJsonPath('data.0.total', 1300);
    }

    public function test_marking_a_project_done_needs_at_least_one_invoice_item(): void
    {
        $project = Project::factory()->for($this->client)->create(['status' => ProjectStatus::WorkingOnIt]);

        $this->putJson('/api/projects/'.$project->id, ['status' => 'done'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->putJson('/api/projects/'.$project->id, ['status' => 'done', 'items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->putJson('/api/projects/'.$project->id, ['status' => 'done', 'items' => [['name' => '', 'cost' => 'abc']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.name', 'items.0.cost']);
        $this->putJson('/api/projects/'.$project->id, ['status' => 'done', 'items' => [['name' => 'Design', 'cost' => -1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.cost');

        $this->assertSame(ProjectStatus::WorkingOnIt, $project->refresh()->status);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_items_are_not_needed_once_a_project_is_done(): void
    {
        $project = Project::factory()->for($this->client)->create(['status' => ProjectStatus::Done]);

        $this->putJson('/api/projects/'.$project->id, ['name' => 'Renamed', 'status' => 'done'])->assertOk();

        $this->assertDatabaseCount('invoices', 0);
        Notification::assertNothingSent();
    }

    public function test_a_pending_project_cannot_be_marked_done(): void
    {
        $project = Project::factory()->pending()->for($this->client)->create(['status' => ProjectStatus::WorkingOnIt]);

        $this->putJson('/api/projects/'.$project->id, ['status' => 'done', 'items' => $this->items()])
            ->assertStatus(409);

        $this->assertSame(ProjectStatus::WorkingOnIt, $project->refresh()->status);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_a_new_project_cannot_start_as_done(): void
    {
        Passport::actingAs($this->team->leader);

        $this->postJson('/api/clients/'.$this->client->id.'/projects/', ['name' => 'Website', 'status' => 'done'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_completing_a_project_notifies_the_admins_and_the_clients_team_leader(): void
    {
        $admins = User::factory(2)->create(['role' => 'admin']);
        $otherLeader = Team::factory()->create()->leader;
        $project = Project::factory()->for($this->client)->create(['status' => ProjectStatus::WorkingOnIt]);

        $this->putJson('/api/projects/'.$project->id, ['status' => 'done', 'items' => $this->items()])->assertOk();

        Notification::assertSentTo([...$admins, $this->team->leader], ProjectCompleted::class);
        Notification::assertNotSentTo([$otherLeader, $this->member], ProjectCompleted::class);
    }

    public function test_the_person_completing_a_project_is_not_notified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->for($this->client)->create(['status' => ProjectStatus::WorkingOnIt]);
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$project->id, ['status' => 'done', 'items' => $this->items()])->assertOk();

        Notification::assertSentTo($admin, ProjectCompleted::class);
        Notification::assertNotSentTo($this->team->leader, ProjectCompleted::class);
    }

    public function test_the_team_leader_can_delete_a_project(): void
    {
        $project = Project::factory()->for($this->client)->create();
        Passport::actingAs($this->team->leader);

        $this->deleteJson('/api/projects/'.$project->id)->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_members_cannot_delete_an_approved_project(): void
    {
        $owned = Project::factory()->for($this->client)->create();
        $helping = $this->helperProject();

        $this->deleteJson('/api/projects/'.$owned->id)->assertForbidden();
        $this->deleteJson('/api/projects/'.$helping->id)->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $owned->id]);
        $this->assertDatabaseHas('projects', ['id' => $helping->id]);
    }

    public function test_the_owner_can_withdraw_a_pending_request(): void
    {
        $project = Project::factory()->pending()->for($this->client)->create();

        $this->deleteJson('/api/projects/'.$project->id)->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_helpers_cannot_withdraw_a_pending_request(): void
    {
        $project = $this->helperProject(['approved_at' => null]);

        $this->deleteJson('/api/projects/'.$project->id)->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_users_cannot_access_projects_of_another_teams_client(): void
    {
        $otherClient = Client::factory()->create();
        $project = Project::factory()->for($otherClient)->create();

        $this->getJson('/api/clients/'.$otherClient->id.'/projects')->assertForbidden();
        $this->getJson('/api/projects/'.$project->id)->assertForbidden();
        $this->postJson('/api/clients/'.$otherClient->id.'/projects', ['name' => 'Sneaky'])->assertForbidden();
        $this->putJson('/api/projects/'.$project->id, ['name' => 'Sneaky'])->assertForbidden();
        $this->deleteJson('/api/projects/'.$project->id)->assertForbidden();
    }

    public function test_the_team_leader_assigns_people_to_a_project(): void
    {
        $project = Project::factory()->for($this->client)->create();
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$project->id, ['user_ids' => [$this->member->id, $this->team->leader_id]])
            ->assertOk()
            ->assertJsonCount(2, 'data.assignees');

        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $this->member->id]);

        $this->getJson('/api/projects/'.$project->id)->assertJsonCount(2, 'data.assignees');

        $this->putJson('/api/projects/'.$project->id, ['user_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.assignees');
    }

    public function test_admins_can_assign_people_when_creating_a_project(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/clients/'.$this->client->id.'/projects', [
            'name' => 'Website redesign',
            'user_ids' => [$this->member->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.assignees.0.id', $this->member->id);
    }

    public function test_people_assigned_to_a_project_are_emailed(): void
    {
        $teammate = User::factory()->create();
        $this->team->members()->attach($teammate);
        $project = Project::factory()->for($this->client)->create();
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$project->id, ['user_ids' => [$this->member->id]])->assertOk();

        Notification::assertSentTo($this->member, ProjectAssigned::class, function (ProjectAssigned $notification, array $channels) use ($project) {
            $mail = $notification->toMail($this->member);

            return $channels === ['mail']
                && $mail->subject === 'You were assigned to a project: '.$project->name
                && str_contains(implode(' ', $mail->introLines), $this->team->leader->name);
        });
        Notification::assertNotSentTo($teammate, ProjectAssigned::class);
    }

    public function test_only_newly_assigned_people_are_emailed(): void
    {
        $teammate = User::factory()->create();
        $this->team->members()->attach($teammate);
        $project = Project::factory()->for($this->client)->hasAttached($this->member, [], 'assignees')->create();
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$project->id, ['user_ids' => [$this->member->id, $teammate->id]])->assertOk();

        Notification::assertSentTo($teammate, ProjectAssigned::class);
        Notification::assertNotSentTo($this->member, ProjectAssigned::class);
    }

    public function test_the_leader_is_not_emailed_for_assigning_themselves(): void
    {
        $project = Project::factory()->for($this->client)->create();
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$project->id, ['user_ids' => [$this->team->leader_id]])->assertOk();

        Notification::assertNotSentTo($this->team->leader, ProjectAssigned::class);
    }

    public function test_people_assigned_when_a_project_is_created_are_emailed(): void
    {
        Passport::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/clients/'.$this->client->id.'/projects', [
            'name' => 'Website redesign',
            'user_ids' => [$this->member->id],
        ])->assertCreated();

        Notification::assertSentTo($this->member, ProjectAssigned::class);
    }

    public function test_only_people_from_the_clients_team_can_be_assigned(): void
    {
        $project = Project::factory()->for($this->client)->create();
        $outsider = User::factory()->create();
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$project->id, ['user_ids' => [$outsider->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_ids.0');

        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id]);
    }

    public function test_members_cannot_assign_people_but_can_still_edit_the_project(): void
    {
        $project = Project::factory()->for($this->client)->create();
        $teammate = User::factory()->create();
        $this->team->members()->attach($teammate);

        $this->putJson('/api/projects/'.$project->id, ['user_ids' => [$teammate->id]])->assertForbidden();
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id, 'user_id' => $teammate->id]);

        $this->putJson('/api/projects/'.$project->id, ['name' => 'Renamed'])->assertOk();
    }

    public function test_people_who_leave_the_team_are_taken_off_its_projects(): void
    {
        $project = Project::factory()->for($this->client)->create();
        $stays = User::factory()->create();
        $this->team->members()->attach($stays);
        $project->assignees()->attach([$this->member->id, $stays->id]);
        Passport::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/teams/'.$this->team->id, [
            'name' => $this->team->name,
            'section_id' => $this->team->section_id,
            'leader_id' => $this->team->leader_id,
            'members_ids' => [$stays->id],
        ])->assertOk();

        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id, 'user_id' => $this->member->id]);
        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $stays->id]);
    }

    public function test_moving_a_client_to_another_team_takes_the_old_team_off_its_projects(): void
    {
        $project = Project::factory()->for($this->client)->create();
        $project->assignees()->attach($this->member);
        Passport::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/clients/'.$this->client->id, [
            'name' => $this->client->name,
            'phone_number' => $this->client->phone_number,
            'team_id' => Team::factory()->create()->id,
        ])->assertOk();

        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id]);
    }

    /**
     * A project of a teammate's client that the member was assigned to as a helper.
     */
    /**
     * The invoice lines sent when marking a project done.
     *
     * @return array<int, array<string, mixed>>
     */
    private function items(): array
    {
        return [['name' => 'Development', 'cost' => 500]];
    }

    private function helperProject(array $attributes = []): Project
    {
        return Project::factory()
            ->for($this->teammatesClient)
            ->hasAttached($this->member, [], 'assignees')
            ->create($attributes);
    }
}
