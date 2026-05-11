<?php

namespace Database\Factories;

use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tournament = Tournament::factory();

        return [
            'tournament_id' => $tournament,
            'week' => 1,
            'home_team_id' => Team::factory()->for($tournament),
            'away_team_id' => Team::factory()->for($tournament),
            'home_goals' => null,
            'away_goals' => null,
            'played_at' => null,
        ];
    }

    public function played(int $homeGoals, int $awayGoals): self
    {
        return $this->state([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'played_at' => now(),
        ]);
    }
}
