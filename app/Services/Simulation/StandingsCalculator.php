<?php

namespace App\Services\Simulation;

use App\Data\StandingRowData;
use App\Data\TeamData;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;

final class StandingsCalculator
{
    public function __construct(private LeagueTableAggregator $aggregator) {}

    /**
     * Derive the league table from played fixtures, sorted by:
     * points → goal difference → goals for → wins → team name.
     *
     * @return Collection<int, StandingRowData>
     */
    public function for(Tournament $tournament): Collection
    {
        /** @var Collection<int, Team> $teams */
        $teams = $tournament->teams()->orderBy('id')->get();

        $stats = $this->aggregator->aggregate(
            $teams,
            $tournament->fixtures()->played()->get(),
        );

        uasort($stats, fn (array $a, array $b) => $this->aggregator->compare($a, $b));

        $teamsById = $teams->keyBy('id');

        return collect($stats)
            ->map(fn (array $row) => new StandingRowData(
                team: TeamData::fromModel($teamsById->get($row['team_id'])),
                played: $row['played'],
                won: $row['won'],
                drawn: $row['drawn'],
                lost: $row['lost'],
                goalsFor: $row['goalsFor'],
                goalsAgainst: $row['goalsAgainst'],
                goalDifference: $row['goalDifference'],
                points: $row['points'],
            ))
            ->values();
    }
}
