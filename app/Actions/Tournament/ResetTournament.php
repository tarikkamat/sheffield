<?php

namespace App\Actions\Tournament;

use App\Enums\TournamentStatus;
use App\Models\Tournament;

final class ResetTournament
{
    public function __invoke(Tournament $tournament): void
    {
        $tournament->fixtures()->update([
            'home_goals' => null,
            'away_goals' => null,
            'played_at' => null,
        ]);

        $tournament->update([
            'current_week' => 0,
            'status' => TournamentStatus::Pending,
        ]);
    }
}
