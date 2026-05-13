<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class StandingRowData extends Data
{
    public function __construct(
        public TeamData $team,
        public int $played,
        public int $won,
        public int $drawn,
        public int $lost,
        public int $goalsFor,
        public int $goalsAgainst,
        public int $goalDifference,
        public int $points,
    ) {}

    /**
     * Flat shape used by the league-table front-end (snake_case keys).
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->team->id,
            'name' => $this->team->name,
            'played' => $this->played,
            'won' => $this->won,
            'drawn' => $this->drawn,
            'lost' => $this->lost,
            'goals_for' => $this->goalsFor,
            'goals_against' => $this->goalsAgainst,
            'goal_difference' => $this->goalDifference,
            'points' => $this->points,
        ];
    }
}
