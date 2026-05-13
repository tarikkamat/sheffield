<?php

namespace App\Services\Simulation;

use App\Models\Fixture;
use App\Models\Team;

/**
 * Shared per-team aggregation kernel for live standings and simulated seasons.
 *
 * @phpstan-type TeamStatsRow array{
 *     team_id: int,
 *     name: string,
 *     played: int,
 *     won: int,
 *     drawn: int,
 *     lost: int,
 *     goalsFor: int,
 *     goalsAgainst: int,
 *     goalDifference: int,
 *     points: int,
 * }
 */
final class LeagueTableAggregator
{
    private int $pointsForWin;

    private int $pointsForDraw;

    public function __construct()
    {
        $this->pointsForWin = (int) config('league.simulation.points_for_win', 3);
        $this->pointsForDraw = (int) config('league.simulation.points_for_draw', 1);
    }

    /**
     * @param  iterable<int, Team>  $teams
     * @param  iterable<int, Fixture|array{home_team_id: int, away_team_id: int, home_goals: int, away_goals: int}>  $fixtures
     * @return array<int, TeamStatsRow>
     */
    public function aggregate(iterable $teams, iterable $fixtures): array
    {
        $stats = [];
        foreach ($teams as $team) {
            $stats[$team->id] = [
                'team_id' => $team->id,
                'name' => $team->name,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goalsFor' => 0,
                'goalsAgainst' => 0,
                'goalDifference' => 0,
                'points' => 0,
            ];
        }

        foreach ($fixtures as $fixture) {
            $home = $fixture instanceof Fixture ? $fixture->home_team_id : $fixture['home_team_id'];
            $away = $fixture instanceof Fixture ? $fixture->away_team_id : $fixture['away_team_id'];
            $homeGoals = (int) ($fixture instanceof Fixture ? $fixture->home_goals : $fixture['home_goals']);
            $awayGoals = (int) ($fixture instanceof Fixture ? $fixture->away_goals : $fixture['away_goals']);

            $stats[$home]['played']++;
            $stats[$away]['played']++;
            $stats[$home]['goalsFor'] += $homeGoals;
            $stats[$home]['goalsAgainst'] += $awayGoals;
            $stats[$away]['goalsFor'] += $awayGoals;
            $stats[$away]['goalsAgainst'] += $homeGoals;

            if ($homeGoals > $awayGoals) {
                $stats[$home]['won']++;
                $stats[$away]['lost']++;
                $stats[$home]['points'] += $this->pointsForWin;
            } elseif ($homeGoals < $awayGoals) {
                $stats[$away]['won']++;
                $stats[$home]['lost']++;
                $stats[$away]['points'] += $this->pointsForWin;
            } else {
                $stats[$home]['drawn']++;
                $stats[$away]['drawn']++;
                $stats[$home]['points'] += $this->pointsForDraw;
                $stats[$away]['points'] += $this->pointsForDraw;
            }
        }

        foreach ($stats as $id => $row) {
            $stats[$id]['goalDifference'] = $row['goalsFor'] - $row['goalsAgainst'];
        }

        return $stats;
    }

    /**
     * Premier-League-style tiebreaker comparator:
     * Points → Goal Difference → Goals For → Wins → Name.
     */
    public function compare(array $a, array $b): int
    {
        return [$b['points'], $b['goalDifference'], $b['goalsFor'], $b['won'], $a['name']]
            <=> [$a['points'], $a['goalDifference'], $a['goalsFor'], $a['won'], $b['name']];
    }
}
