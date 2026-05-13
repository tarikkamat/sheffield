<?php

namespace App\Http\Controllers\Tournament;

use App\Contracts\Simulation\ChampionshipPredictor;
use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Tournament;
use App\Services\Simulation\StandingsCalculator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SimulationController extends Controller
{
    public function show(
        Tournament $tournament,
        StandingsCalculator $standings,
        ChampionshipPredictor $predictor,
    ): Response {
        $showPredictions = $tournament->shouldRevealPredictions();

        return Inertia::render('Tournament/Simulation', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->value,
                'totalWeeks' => $tournament->totalWeeks(),
                'hasFixtures' => $tournament->hasFixtures(),
            ],
            'currentWeek' => $tournament->current_week,
            'standings' => $standings->for($tournament)->map->toRow()->all(),
            'fixtures' => $this->mapFixtures($this->displayWeekFixtures($tournament)),
            'history' => $this->playedHistory($tournament),
            'predictions' => $showPredictions
                ? $predictor->for($tournament)->map->toRow()->all()
                : [],
            'showPredictions' => $showPredictions,
            'revealFromWeek' => (int) config('league.predictor.reveal_from_week', 4),
        ]);
    }

    /**
     * @return Collection<int, Fixture>
     */
    private function displayWeekFixtures(Tournament $tournament): Collection
    {
        $week = $tournament->current_week === 0 ? 1 : $tournament->current_week;

        return $tournament->fixtures()
            ->forWeek($week)
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, array{week: int, matches: array<int, array<string, mixed>>}>
     */
    private function playedHistory(Tournament $tournament): array
    {
        return $tournament->fixtures()
            ->played()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('week')
            ->orderBy('id')
            ->get()
            ->groupBy('week')
            ->sortKeysDesc()
            ->map(fn (Collection $fixtures, int $week) => [
                'week' => $week,
                'matches' => $this->mapFixtures($fixtures),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Fixture>  $fixtures
     * @return array<int, array<string, mixed>>
     */
    private function mapFixtures(Collection $fixtures): array
    {
        return $fixtures->map(fn (Fixture $fixture) => [
            'id' => $fixture->id,
            'week' => $fixture->week,
            'home' => $fixture->homeTeam->name,
            'away' => $fixture->awayTeam->name,
            'home_goals' => $fixture->home_goals,
            'away_goals' => $fixture->away_goals,
            'is_played' => $fixture->isPlayed(),
            'score' => $fixture->isPlayed()
                ? "{$fixture->home_goals} - {$fixture->away_goals}"
                : 'vs',
        ])->all();
    }
}
