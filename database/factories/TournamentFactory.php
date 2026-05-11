<?php

namespace Database\Factories;

use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Premier League '.fake()->year(),
            'current_week' => 0,
            'status' => TournamentStatus::Pending,
        ];
    }

    public function inProgress(int $week = 1): self
    {
        return $this->state([
            'current_week' => $week,
            'status' => TournamentStatus::InProgress,
        ]);
    }

    public function completed(): self
    {
        return $this->state([
            'status' => TournamentStatus::Completed,
        ]);
    }
}
