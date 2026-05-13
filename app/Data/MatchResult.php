<?php

namespace App\Data;

final readonly class MatchResult
{
    public function __construct(
        public int $homeGoals,
        public int $awayGoals,
    ) {}
}
