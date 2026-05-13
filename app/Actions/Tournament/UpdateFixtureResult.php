<?php

namespace App\Actions\Tournament;

use App\Models\Fixture;

final class UpdateFixtureResult
{
    public function __invoke(Fixture $fixture, int $homeGoals, int $awayGoals): Fixture
    {
        $fixture->update([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'played_at' => $fixture->played_at ?? now(),
        ]);

        return $fixture->refresh();
    }
}
