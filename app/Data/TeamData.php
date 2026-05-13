<?php

namespace App\Data;

use App\Models\Team;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapName(SnakeCaseMapper::class)]
final class TeamData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int|Optional $strength,
        public int|Optional $attackPower,
        public int|Optional $defensePower,
        public int|Optional $goalkeeperPower,
        public int|Optional $supporterPower,
    ) {}

    /**
     * Lean projection — only id + name. Used inside standings/predictions.
     */
    public static function fromModel(Team $team): self
    {
        return new self(
            id: $team->id,
            name: $team->name,
            strength: Optional::create(),
            attackPower: Optional::create(),
            defensePower: Optional::create(),
            goalkeeperPower: Optional::create(),
            supporterPower: Optional::create(),
        );
    }

    /**
     * Full projection — every power stat. Used by the API roster response.
     */
    public static function fromModelWithProfile(Team $team): self
    {
        return new self(
            id: $team->id,
            name: $team->name,
            strength: $team->strength,
            attackPower: $team->attack_power,
            defensePower: $team->defense_power,
            goalkeeperPower: $team->goalkeeper_power,
            supporterPower: $team->supporter_power,
        );
    }
}
