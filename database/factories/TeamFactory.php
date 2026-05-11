<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => fake()->unique()->company(),
            'strength' => fake()->numberBetween(60, 95),
        ];
    }

    public function withStrength(int $strength): self
    {
        return $this->state(['strength' => $strength]);
    }
}
