<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'phone_number' => fake()->e164PhoneNumber(),
            'team_id' => Team::factory(),
            'created_by' => User::factory(),
        ];
    }
}
