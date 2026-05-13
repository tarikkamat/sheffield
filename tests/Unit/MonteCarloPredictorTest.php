<?php

use App\Actions\Tournament\GenerateFixtures;
use App\Actions\Tournament\PlayWeek;
use App\Contracts\Simulation\MatchSimulator;
use App\Data\MatchResult;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Simulation\LeagueTableAggregator;
use App\Services\Simulation\MonteCarloPredictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function predictor(int $iterations = 200): MonteCarloPredictor
{
    return new MonteCarloPredictor(
        app(MatchSimulator::class),
        new LeagueTableAggregator,
        $iterations,
    );
}

it('returns one row per team', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();
    app(GenerateFixtures::class)($tournament);

    expect(predictor(50)->for($tournament))->toHaveCount(4);
});

it('returns probabilities that sum to exactly 100 percent', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();
    app(GenerateFixtures::class)($tournament);

    $total = predictor(300)->for($tournament)->sum('chance');

    expect(round((float) $total, 1))->toBe(100.0);
});

it('gives a mathematically guaranteed champion 100 percent', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();
    app(GenerateFixtures::class)($tournament);

    $this->app->bind(MatchSimulator::class, fn () => new class implements MatchSimulator
    {
        public function simulate(Team $home, Team $away): MatchResult
        {
            return new MatchResult(homeGoals: 5, awayGoals: 0);
        }
    });

    for ($i = 0; $i < 5; $i++) {
        app(PlayWeek::class)($tournament);
        $tournament->refresh();
    }

    $predictions = predictor(50)->for($tournament);

    expect($predictions->first()->chance)->toBe(100.0);
    expect($predictions->skip(1)->sum('chance'))->toBe(0.0);
});

it('gives mathematically eliminated teams 0 percent', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();
    app(GenerateFixtures::class)($tournament);

    $this->app->bind(MatchSimulator::class, fn () => new class implements MatchSimulator
    {
        public function simulate(Team $home, Team $away): MatchResult
        {
            return new MatchResult(homeGoals: 3, awayGoals: 0);
        }
    });

    for ($i = 0; $i < 5; $i++) {
        app(PlayWeek::class)($tournament);
        $tournament->refresh();
    }

    $predictions = predictor(50)->for($tournament);
    $zeroChance = $predictions->where('chance', 0.0)->count();

    expect($zeroChance)->toBeGreaterThanOrEqual(3);
});
