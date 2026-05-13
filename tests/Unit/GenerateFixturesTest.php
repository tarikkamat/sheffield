<?php

use App\Actions\Tournament\GenerateFixtures;
use App\Enums\TournamentStatus;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('generates 2*(N-1) weeks for N teams', function (int $teamCount, int $expectedWeeks) {
    $tournament = Tournament::factory()->has(Team::factory()->count($teamCount))->create();

    $fixtures = app(GenerateFixtures::class)($tournament);

    expect($fixtures->pluck('week')->unique()->count())->toBe($expectedWeeks);
    expect($fixtures->count())->toBe($expectedWeeks * ($teamCount / 2));
})->with([
    '4 teams → 6 weeks' => [4, 6],
    '6 teams → 10 weeks' => [6, 10],
    '8 teams → 14 weeks' => [8, 14],
]);

it('generates N/2 fixtures per week', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(6))->create();

    $fixtures = app(GenerateFixtures::class)($tournament);

    foreach ($fixtures->groupBy('week') as $weekFixtures) {
        expect($weekFixtures)->toHaveCount(3);
    }
});

it('has each team play exactly once per week', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(6))->create();

    $fixtures = app(GenerateFixtures::class)($tournament);

    foreach ($fixtures->groupBy('week') as $weekFixtures) {
        $teamIds = $weekFixtures->flatMap(fn (Fixture $f) => [$f->home_team_id, $f->away_team_id]);
        expect($teamIds->unique()->count())->toBe(6);
    }
});

it('plays each pair twice with reversed home and away', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();

    $fixtures = app(GenerateFixtures::class)($tournament);

    $pairs = $fixtures->groupBy(function (Fixture $f) {
        $ids = [$f->home_team_id, $f->away_team_id];
        sort($ids);

        return implode('-', $ids);
    });

    expect($pairs)->toHaveCount(6);

    foreach ($pairs as $pairFixtures) {
        expect($pairFixtures)->toHaveCount(2);
        expect($pairFixtures->pluck('home_team_id')->unique()->count())->toBe(2);
    }
});

it('throws when team count is odd', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(3))->create();

    expect(fn () => app(GenerateFixtures::class)($tournament))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when team count is less than two', function () {
    $tournament = Tournament::factory()->create();

    expect(fn () => app(GenerateFixtures::class)($tournament))
        ->toThrow(InvalidArgumentException::class);
});

it('replaces existing fixtures when invoked twice', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();

    app(GenerateFixtures::class)($tournament);
    $firstCount = $tournament->fixtures()->count();

    app(GenerateFixtures::class)($tournament);
    $secondCount = $tournament->fixtures()->count();

    expect($firstCount)->toBe(12);
    expect($secondCount)->toBe(12);
});

it('resets current_week and status when regenerating after completion', function () {
    $tournament = Tournament::factory()
        ->inProgress(6)
        ->completed()
        ->has(Team::factory()->count(4))
        ->create();

    app(GenerateFixtures::class)($tournament);

    $tournament->refresh();
    expect($tournament->current_week)->toBe(0);
    expect($tournament->status)->toBe(TournamentStatus::Pending);
});
