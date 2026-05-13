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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $strength = fake()->numberBetween(60, 95);

        return [
            'tournament_id' => Tournament::factory(),
            'name' => fake()->unique()->company(),
            'strength' => $strength,
            'attack_power' => $this->wiggle($strength),
            'defense_power' => $this->wiggle($strength),
            'goalkeeper_power' => $this->wiggle($strength),
            'supporter_power' => $this->wiggle($strength),
        ];
    }

    public function withStrength(int $strength): self
    {
        return $this->state([
            'strength' => $strength,
            'attack_power' => $strength,
            'defense_power' => $strength,
            'goalkeeper_power' => $strength,
            'supporter_power' => $strength,
        ]);
    }

    /**
     * Generate a stat within +/-5 of the base, clamped to [50, 99].
     */
    private function wiggle(int $base): int
    {
        return max(50, min(99, $base + fake()->numberBetween(-5, 5)));
    }
}
