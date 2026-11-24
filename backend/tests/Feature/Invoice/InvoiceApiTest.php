<?php

namespace Tests\Feature\Invoice;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $team = Team::factory()->create();
        $this->member = User::factory()->create();
        $team->members()->attach($this->member);
        $this->client = Client::factory()->for($team)->create();

        Passport::actingAs($this->member);
        Storage::fake('public');
    }

    public function test_authenticated_users_can_list_a_projects_invoices(): void
    {
        $project = $this->helperProject();
        $invoice = Invoice::factory()->for($project)->create(['items' => [['name' => 'Design', 'cost' => 120]]]);

        $this->getJson('/api/projects/'.$project->id.'/invoices')
            ->assertOk()
            ->assertJsonPath('data.0.id', $invoice->id)
            ->assertJsonPath('data.0.items.0.name', 'Design')
            ->assertJsonPath('data.0.total', 120)
            ->assertJsonPath('data.0.creator.id', $invoice->created_by);
    }

    public function test_authenticated_users_can_view_an_invoice(): void
    {
        $invoice = Invoice::factory()->for($this->helperProject())->create();

        $this->getJson('/api/invoices/'.$invoice->id)
            ->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonMissingPath('data.invoice');
    }

    public function test_authenticated_users_can_create_an_invoice_from_items(): void
    {
        $project = $this->helperProject();

        $this->postJson('/api/projects/'.$project->id.'/invoices', [
            'items' => [
                ['name' => 'Design', 'cost' => '1200.50'],
                ['name' => 'Hosting', 'cost' => 99.5],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.total', 1300)
            ->assertJsonPath('data.created_by', $this->member->id);

        $invoice = $project->invoices()->sole();
        $this->assertSame([
            ['name' => 'Design', 'cost' => 1200.5],
            ['name' => 'Hosting', 'cost' => 99.5],
        ], $invoice->items);
        $this->assertSame($this->member->id, $invoice->created_by);
        // Nothing is written to disk: the PDF is rendered on download.
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_authenticated_users_can_update_an_invoices_items(): void
    {
        $invoice = Invoice::factory()->for($this->helperProject())->create();

        $this->putJson('/api/invoices/'.$invoice->id, ['items' => [['name' => 'Support', 'cost' => 40]]])
            ->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.items.0.name', 'Support')
            ->assertJsonPath('data.total', 40);

        $this->assertSame([['name' => 'Support', 'cost' => 40.0]], $invoice->refresh()->items);
    }

    public function test_authenticated_users_can_delete_an_invoice(): void
    {
        $invoice = Invoice::factory()->for($this->helperProject())->create();

        $this->deleteJson('/api/invoices/'.$invoice->id)->assertNoContent();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_authenticated_users_can_download_an_invoice_pdf(): void
    {
        $invoice = Invoice::factory()->for($this->helperProject())->create();

        $response = $this->get('/api/invoices/'.$invoice->id.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload("invoice-{$invoice->id}.pdf");

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_an_invoice_needs_valid_items(): void
    {
        $project = $this->helperProject();

        $this->postJson('/api/projects/'.$project->id.'/invoices', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->postJson('/api/projects/'.$project->id.'/invoices', ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->postJson('/api/projects/'.$project->id.'/invoices', ['items' => [['name' => '', 'cost' => -1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.name', 'items.0.cost']);

        $invoice = Invoice::factory()->for($project)->create();
        $this->putJson('/api/invoices/'.$invoice->id, ['items' => [['name' => 'Design', 'cost' => 'abc']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.cost');

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_guests_cannot_access_invoices(): void
    {
        $invoice = Invoice::factory()->create();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/invoices/'.$invoice->id)->assertUnauthorized();
        $this->getJson('/api/invoices/'.$invoice->id.'/pdf')->assertUnauthorized();
    }

    public function test_users_cannot_access_invoices_of_another_teams_client(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->for($project)->create();

        $this->getJson('/api/projects/'.$project->id.'/invoices')->assertForbidden();
        $this->getJson('/api/invoices/'.$invoice->id)->assertForbidden();
        $this->getJson('/api/invoices/'.$invoice->id.'/pdf')->assertForbidden();
        $this->postJson('/api/projects/'.$project->id.'/invoices', ['items' => $this->items()])->assertForbidden();
        $this->putJson('/api/invoices/'.$invoice->id, ['items' => $this->items()])->assertForbidden();
        $this->deleteJson('/api/invoices/'.$invoice->id)->assertForbidden();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_members_cannot_access_invoices_of_a_project_they_are_not_on(): void
    {
        $project = Project::factory()->for($this->client)->create();

        $this->getJson('/api/projects/'.$project->id.'/invoices')->assertForbidden();
        $this->postJson('/api/projects/'.$project->id.'/invoices', ['items' => $this->items()])->assertForbidden();
    }

    public function test_a_pending_project_cannot_get_invoices(): void
    {
        $project = Project::factory()->pending()->for($this->client)->hasAttached($this->member, [], 'assignees')->create();

        $this->postJson('/api/projects/'.$project->id.'/invoices', ['items' => $this->items()])
            ->assertStatus(409)
            ->assertJsonPath('message', "The project is waiting for the team leader's approval.");

        $this->assertDatabaseCount('invoices', 0);
    }

    private function helperProject(): Project
    {
        return Project::factory()->for($this->client)->hasAttached($this->member, [], 'assignees')->create();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(): array
    {
        return [['name' => 'Development', 'cost' => 500]];
    }
}
