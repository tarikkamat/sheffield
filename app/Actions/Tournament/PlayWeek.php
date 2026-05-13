<?php

namespace App\Actions\Tournament;

use App\Contracts\Simulation\MatchSimulator;
use App\Enums\TournamentStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Fixture;
use App\Models\Tournament;
use Illuminate\Support\Collection;

final class PlayWeek
{
    public function __construct(private MatchSimulator $simulator) {}

    /**
     * Play every pending fixture for the next week and advance the tournament.
     *
     * @return Collection<int, Fixture>
     */
    public function __invoke(Tournament $tournament): Collection
    {
        $totalWeeks = $tournament->totalWeeks();
        $nextWeek = $tournament->current_week + 1;

        if ($nextWeek > $totalWeeks) {
            throw new InvalidTournamentStateException("Tournament already completed (week {$tournament->current_week} of {$totalWeeks}).");
        }

        /** @var Collection<int, Fixture> $pending */
        $pending = $tournament->fixtures()
            ->forWeek($nextWeek)
            ->pending()
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        if ($pending->isEmpty()) {
            throw new InvalidTournamentStateException("No pending fixtures for week {$nextWeek}.");
        }

        foreach ($pending as $fixture) {
            $result = $this->simulator->simulate($fixture->homeTeam, $fixture->awayTeam);
            $fixture->update([
                'home_goals' => $result->homeGoals,
                'away_goals' => $result->awayGoals,
                'played_at' => now(),
            ]);
        }

        $tournament->update([
            'current_week' => $nextWeek,
            'status' => $nextWeek >= $totalWeeks ? TournamentStatus::Completed : TournamentStatus::InProgress,
        ]);

        return $pending->fresh();
    }
}
