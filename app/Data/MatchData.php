<?php

namespace App\Data;

use App\Models\Fixture;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class MatchData extends Data
{
    public function __construct(
        public int $id,
        public int $week,
        public TeamData $homeTeam,
        public TeamData $awayTeam,
        public ?int $homeGoals,
        public ?int $awayGoals,
        public ?string $playedAt,
        public bool $isPlayed,
    ) {}

    public static function fromModel(Fixture $fixture): self
    {
        return new self(
            id: $fixture->id,
            week: $fixture->week,
            homeTeam: TeamData::fromModel($fixture->homeTeam),
            awayTeam: TeamData::fromModel($fixture->awayTeam),
            homeGoals: $fixture->home_goals,
            awayGoals: $fixture->away_goals,
            playedAt: $fixture->played_at?->toIso8601String(),
            isPlayed: $fixture->isPlayed(),
        );
    }
}
