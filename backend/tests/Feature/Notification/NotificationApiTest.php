<?php

namespace Tests\Feature\Notification;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ProjectAssigned;
use App\Notifications\ProjectCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $member;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->team = Team::factory()->create();
        $this->member = User::factory()->create();
        $this->team->members()->attach($this->member);
        $client = Client::factory()->for($this->team)->for($this->member, 'creator')->create();
        $this->project = Project::factory()->for($client)->create(['status' => ProjectStatus::WorkingOnIt]);
    }

    public function test_the_team_leader_sees_a_notification_when_a_member_completes_a_project(): void
    {
        $this->complete();
        Passport::actingAs($this->team->leader);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'ProjectCompleted')
            ->assertJsonPath('data.0.details.project_id', $this->project->id)
            ->assertJsonPath('data.0.details.completed_by', $this->member->name)
            ->assertJsonPath('data.0.details.invoice_total', 500)
            ->assertJsonPath('data.0.read_at', null);
    }

    public function test_completing_a_project_emails_the_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Event::fake([MessageSent::class]);

        $this->complete();

        Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => collect($event->message->getTo())
            ->contains(fn ($address) => $address->getAddress() === $admin->email)
            && $event->message->getAttachments()[0]->getFilename() === 'invoice.pdf');
    }

    public function test_the_completion_email_is_queued_but_the_in_app_notification_is_stored_right_away(): void
    {
        // The queue the app uses (.env); phpunit.xml runs jobs synchronously.
        config(['queue.default' => 'database']);

        $this->complete();

        $this->assertSame(1, $this->team->leader->notifications()->count());
        $this->assertDatabaseCount('jobs', 1);
        $job = unserialize(json_decode(DB::table('jobs')->value('payload'), true)['data']['command']);
        $this->assertInstanceOf(ProjectCompleted::class, $job->notification);
        $this->assertSame(['mail'], $job->channels);
    }

    public function test_the_assignment_email_is_queued(): void
    {
        Queue::fake();
        Passport::actingAs($this->team->leader);

        $this->putJson('/api/projects/'.$this->project->id, ['user_ids' => [$this->member->id]])->assertOk();

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof ProjectAssigned
            && $job->notifiables->contains($this->member));
    }

    public function test_the_unread_filter_lists_only_unread_notifications(): void
    {
        $this->complete();
        Passport::actingAs($this->team->leader);
        $id = $this->getJson('/api/notifications')->json('data.0.id');

        $this->getJson('/api/notifications?unread=1')->assertOk()->assertJsonPath('meta.total', 1);

        $this->putJson('/api/notifications/'.$id, ['read' => true])
            ->assertOk()
            ->assertJsonPath('data.id', $id);
        $this->assertNotNull($this->team->leader->notifications()->first()->read_at);

        $this->getJson('/api/notifications?unread=1')->assertOk()->assertJsonPath('meta.total', 0);

        $this->putJson('/api/notifications/'.$id, ['read' => false])
            ->assertOk()
            ->assertJsonPath('data.read_at', null);
    }

    public function test_marking_a_notification_needs_the_read_flag(): void
    {
        $this->complete();
        Passport::actingAs($this->team->leader);
        $id = $this->getJson('/api/notifications')->json('data.0.id');

        $this->putJson('/api/notifications/'.$id, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('read');
    }

    public function test_users_only_see_and_change_their_own_notifications(): void
    {
        $this->complete();
        $id = $this->team->leader->notifications()->value('id');
        Passport::actingAs($this->member);

        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(0, 'data');
        $this->putJson('/api/notifications/'.$id, ['read' => true])->assertNotFound();
    }

    public function test_guests_cannot_list_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    private function complete(): void
    {
        Passport::actingAs($this->member);

        $this->putJson('/api/projects/'.$this->project->id, [
            'status' => 'done',
            'items' => [['name' => 'Development', 'cost' => 500]],
        ])->assertOk();
    }
}
