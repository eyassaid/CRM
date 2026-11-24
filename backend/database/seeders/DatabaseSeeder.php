<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\Section;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createPersonalAccessClient();

        User::factory()->create([
            'role' => 'admin',
            'name' => 'eyas',
            'email' => 'ey6959@gmail.com',
            'password' => 'password',
        ]);

        // Each section has two teams; each team has a leader and three members who bring clients.
        Section::factory(3)->create()->each(function (Section $section) {
            Team::factory(2)
                ->for($section)
                // A state closure, so each team gets its own leader (`for()` would share one).
                ->state(fn () => ['leader_id' => User::factory()->teamLeader()])
                ->create()
                ->each(function (Team $team) {
                    $members = User::factory(3)->create();
                    $team->members()->attach($members);

                    foreach ($members as $member) {
                        Client::factory(fake()->numberBetween(0, 3))
                            ->for($team)
                            ->for($member, 'creator')
                            ->has(Project::factory(fake()->numberBetween(1, 3)))
                            ->create()
                            // The member who brought the client works on its projects; some get a teammate as a helper.
                            ->each(fn (Client $client) => $client->projects->each(
                                fn (Project $project) => $project->assignees()->attach(
                                    $members->except($member->id)->random(fake()->numberBetween(0, 1)),
                                ),
                            ));
                    }
                });
        });
    }

    /**
     * Login and register issue tokens with createToken(), which needs a personal access client.
     * migrate:fresh deletes it, so the seeder recreates it when it is missing.
     */
    private function createPersonalAccessClient(): void
    {
        $clients = app(ClientRepository::class);

        try {
            $clients->personalAccessClient('users');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Personal Access Client', 'users');
        }
    }
}
