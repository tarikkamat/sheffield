<?php

namespace App\Actions\Tournament;

use App\Enums\TournamentStatus;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class GenerateFixtures
{
    /**
     * Generate a double round-robin fixture list using the circle method (Berger tables).
     *
     * @return Collection<int, Fixture>
     */
    public function __invoke(Tournament $tournament): Collection
    {
        /** @var EloquentCollection<int, Team> $teams */
        $teams = $tournament->teams()->orderBy('id')->get();
        $teamCount = $teams->count();

        if ($teamCount < 2 || $teamCount % 2 !== 0) {
            throw new InvalidArgumentException(
                "Tournament must have an even number of teams (>=2), got {$teamCount}."
            );
        }

        $tournament->fixtures()->delete();

        $rows = $this->buildFixtureRows($tournament, $teams);
        Fixture::insert($rows);

        $tournament->update([
            'current_week' => 0,
            'status' => TournamentStatus::Pending,
        ]);

        return $tournament->fixtures()
            ->orderBy('week')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, Team>  $teams
     * @return array<int, array<string, mixed>>
     */
    private function buildFixtureRows(Tournament $tournament, EloquentCollection $teams): array
    {
        $now = now();
        $firstHalf = $this->buildFirstHalfWeeks($teams);
        $allWeeks = array_merge($firstHalf, $this->mirrorWeeks($firstHalf));

        $rows = [];
        foreach ($allWeeks as $weekIndex => $matches) {
            foreach ($matches as $match) {
                $rows[] = [
                    'tournament_id' => $tournament->id,
                    'week' => $weekIndex + 1,
                    'home_team_id' => $match['home'],
                    'away_team_id' => $match['away'],
                    'home_goals' => null,
                    'away_goals' => null,
                    'played_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $rows;
    }

    /**
     * Circle method: anchor the first team, rotate the rest. Produces N-1 rounds.
     *
     * @param  EloquentCollection<int, Team>  $teams
     * @return array<int, array<int, array{home: int, away: int}>>
     */
    private function buildFirstHalfWeeks(EloquentCollection $teams): array
    {
        $count = $teams->count();
        $anchor = $teams->first();
        /** @var array<int, Team> $rotation */
        $rotation = $teams->slice(1)->values()->all();

        $weeks = [];
        for ($round = 0; $round < $count - 1; $round++) {
            $circle = array_merge([$anchor], $rotation);
            $matches = [];

            for ($pair = 0; $pair < $count / 2; $pair++) {
                $top = $circle[$pair];
                $bottom = $circle[$count - 1 - $pair];

                if ($pair === 0 && $round % 2 === 1) {
                    $matches[] = ['home' => $bottom->id, 'away' => $top->id];
                } else {
                    $matches[] = ['home' => $top->id, 'away' => $bottom->id];
                }
            }

            $weeks[] = $matches;
            array_unshift($rotation, array_pop($rotation));
        }

        return $weeks;
    }

    /**
     * @param  array<int, array<int, array{home: int, away: int}>>  $weeks
     * @return array<int, array<int, array{home: int, away: int}>>
     */
    private function mirrorWeeks(array $weeks): array
    {
        return array_map(
            fn (array $week) => array_map(
                fn (array $match) => ['home' => $match['away'], 'away' => $match['home']],
                $week,
            ),
            $weeks,
        );
    }
}
