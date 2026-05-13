<?php

use App\Actions\Tournament\GenerateFixtures;
use App\Actions\Tournament\PlayWeek;
use App\Contracts\Simulation\MatchSimulator;
use App\Data\MatchResult;
use App\Enums\TournamentStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(MatchSimulator::class, fn () => new class implements MatchSimulator
    {
        public function simulate(Team $home, Team $away): MatchResult
        {
            return new MatchResult(homeGoals: 1, awayGoals: 0);
        }
    });
});

it('plays every fixture in the next week and advances current_week', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();
    app(GenerateFixtures::class)($tournament);

    $played = app(PlayWeek::class)($tournament);

    expect($played)->toHaveCount(2);
    expect($tournament->fresh()->current_week)->toBe(1);
    expect($tournament->fresh()->status)->toBe(TournamentStatus::InProgress);
    foreach ($played as $fixture) {
        expect($fixture->isPlayed())->toBeTrue();
        expect($fixture->home_goals)->toBe(1);
        expect($fixture->away_goals)->toBe(0);
    }
});

it('marks the tournament completed after the final week', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();
    app(GenerateFixtures::class)($tournament);

    for ($i = 0; $i < 6; $i++) {
        app(PlayWeek::class)($tournament);
        $tournament->refresh();
    }

    expect($tournament->current_week)->toBe(6);
    expect($tournament->status)->toBe(TournamentStatus::Completed);
});

it('throws when called after the tournament is complete', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();
    app(GenerateFixtures::class)($tournament);

    for ($i = 0; $i < 6; $i++) {
        app(PlayWeek::class)($tournament);
        $tournament->refresh();
    }

    expect(fn () => app(PlayWeek::class)($tournament))->toThrow(InvalidTournamentStateException::class);
});

it('throws when the next week has no pending fixtures', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();

    expect(fn () => app(PlayWeek::class)($tournament))->toThrow(InvalidTournamentStateException::class);
});
