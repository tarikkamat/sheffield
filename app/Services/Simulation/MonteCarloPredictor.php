<?php

namespace App\Services\Simulation;

use App\Contracts\Simulation\ChampionshipPredictor;
use App\Contracts\Simulation\MatchSimulator;
use App\Data\PredictionRowData;
use App\Data\TeamData;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;

/**
 * Hybrid championship predictor:
 *  1) Mathematical elimination — teams that can't reach the leader → 0%.
 *  2) Mathematical guarantee — a runaway leader → 100%, everyone else 0%.
 *  3) Monte Carlo simulation over the remaining fixtures for everyone else.
 *  4) Normalise percentages so the visible table sums to exactly 100%.
 */
final class MonteCarloPredictor implements ChampionshipPredictor
{
    private int $iterations;

    public function __construct(
        private MatchSimulator $simulator,
        private LeagueTableAggregator $aggregator,
        ?int $iterations = null,
    ) {
        $this->iterations = $iterations ?? (int) config('league.predictor.iterations', 1000);
    }

    /**
     * @return Collection<int, PredictionRowData>
     */
    public function for(Tournament $tournament): Collection
    {
        /** @var Collection<int, Team> $teams */
        $teams = $tournament->teams()->orderBy('id')->get();

        /** @var Collection<int, Fixture> $played */
        $played = $tournament->fixtures()->played()->get();

        /** @var Collection<int, Fixture> $pending */
        $pending = $tournament->fixtures()->pending()->with(['homeTeam', 'awayTeam'])->get();

        $currentStats = $this->aggregator->aggregate($teams, $played);

        [$eliminated, $guaranteed] = $this->classify($teams, $currentStats, $pending);

        $chances = $this->initialiseChances($teams);

        if ($guaranteed !== null) {
            $chances[$guaranteed] = 100.0;

            return $this->buildRows($teams, $chances);
        }

        $contenders = $teams->reject(fn (Team $t) => isset($eliminated[$t->id]));

        if ($contenders->isEmpty() || $pending->isEmpty()) {
            return $this->buildRows($teams, $chances);
        }

        $titleCounts = $contenders->mapWithKeys(fn (Team $t) => [$t->id => 0])->all();

        for ($i = 0; $i < $this->iterations; $i++) {
            $winnerId = $this->simulateSeason($teams, $played, $pending);
            if (isset($titleCounts[$winnerId])) {
                $titleCounts[$winnerId]++;
            }
        }

        foreach ($titleCounts as $teamId => $titles) {
            $chances[$teamId] = round(($titles / $this->iterations) * 100, 1);
        }

        $chances = $this->normalise($chances);

        return $this->buildRows($teams, $chances);
    }

    /**
     * Find mathematically eliminated teams and (if any) the guaranteed champion.
     *
     * @param  Collection<int, Team>  $teams
     * @param  array<int, array<string, mixed>>  $currentStats
     * @param  Collection<int, Fixture>  $pending
     * @return array{0: array<int, true>, 1: int|null}
     */
    private function classify(Collection $teams, array $currentStats, Collection $pending): array
    {
        $pointsForWin = (int) config('league.simulation.points_for_win', 3);

        $remainingPerTeam = [];
        foreach ($teams as $team) {
            $remainingPerTeam[$team->id] = 0;
        }
        foreach ($pending as $fixture) {
            $remainingPerTeam[$fixture->home_team_id]++;
            $remainingPerTeam[$fixture->away_team_id]++;
        }

        $current = [];
        $maxPossible = [];
        foreach ($teams as $team) {
            $current[$team->id] = $currentStats[$team->id]['points'];
            $maxPossible[$team->id] = $current[$team->id] + $pointsForWin * $remainingPerTeam[$team->id];
        }

        $eliminated = [];
        foreach ($teams as $team) {
            foreach ($teams as $rival) {
                if ($rival->id === $team->id) {
                    continue;
                }

                if ($current[$rival->id] > $maxPossible[$team->id]) {
                    $eliminated[$team->id] = true;
                    break;
                }
            }
        }

        $guaranteed = null;
        foreach ($teams as $team) {
            $isGuaranteed = true;
            foreach ($teams as $rival) {
                if ($rival->id === $team->id) {
                    continue;
                }

                if ($maxPossible[$rival->id] >= $current[$team->id]) {
                    $isGuaranteed = false;
                    break;
                }
            }

            if ($isGuaranteed) {
                $guaranteed = $team->id;
                break;
            }
        }

        return [$eliminated, $guaranteed];
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, float>
     */
    private function initialiseChances(Collection $teams): array
    {
        return $teams->mapWithKeys(fn (Team $team) => [$team->id => 0.0])->all();
    }

    /**
     * Adjust rounded percentages so survivors sum to exactly 100.
     *
     * @param  array<int, float>  $chances
     * @return array<int, float>
     */
    private function normalise(array $chances): array
    {
        $sum = array_sum($chances);
        if ($sum <= 0.0) {
            return $chances;
        }

        $diff = round(100.0 - $sum, 1);
        if (abs($diff) < 0.05) {
            return $chances;
        }

        $leaderId = array_key_first($chances);
        $leaderChance = $chances[$leaderId];
        foreach ($chances as $id => $chance) {
            if ($chance > $leaderChance) {
                $leaderChance = $chance;
                $leaderId = $id;
            }
        }

        $chances[$leaderId] = round($chances[$leaderId] + $diff, 1);

        return $chances;
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  array<int, float>  $chances
     * @return Collection<int, PredictionRowData>
     */
    private function buildRows(Collection $teams, array $chances): Collection
    {
        return $teams
            ->map(fn (Team $team) => new PredictionRowData(
                team: TeamData::fromModel($team),
                chance: $chances[$team->id] ?? 0.0,
            ))
            ->sort(fn (PredictionRowData $a, PredictionRowData $b) => [$b->chance, $a->team->name] <=> [$a->chance, $b->team->name]
            )
            ->values();
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, Fixture>  $played
     * @param  Collection<int, Fixture>  $pending
     */
    private function simulateSeason(Collection $teams, Collection $played, Collection $pending): int
    {
        $simulated = [];
        foreach ($pending as $fixture) {
            $result = $this->simulator->simulate($fixture->homeTeam, $fixture->awayTeam);
            $simulated[] = [
                'home_team_id' => $fixture->home_team_id,
                'away_team_id' => $fixture->away_team_id,
                'home_goals' => $result->homeGoals,
                'away_goals' => $result->awayGoals,
            ];
        }

        $stats = $this->aggregator->aggregate(
            $teams,
            (function () use ($played, $simulated) {
                yield from $played;
                yield from $simulated;
            })(),
        );

        uasort($stats, fn (array $a, array $b) => $this->aggregator->compare($a, $b));

        return (int) array_key_first($stats);
    }
}
