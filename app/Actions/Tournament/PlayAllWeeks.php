<?php

namespace App\Actions\Tournament;

use App\Enums\TournamentStatus;
use App\Models\Tournament;

final class PlayAllWeeks
{
    public function __construct(private PlayWeek $playWeek) {}

    public function __invoke(Tournament $tournament): void
    {
        $totalWeeks = $tournament->totalWeeks();

        while ($tournament->current_week < $totalWeeks) {
            ($this->playWeek)($tournament);
            $tournament->refresh();
        }

        if ($tournament->status !== TournamentStatus::Completed) {
            $tournament->update(['status' => TournamentStatus::Completed]);
        }
    }
}
