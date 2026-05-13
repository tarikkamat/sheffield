<?php

use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Simulation\StandingsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns all teams with zero stats when no fixtures are played', function () {
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();

    $standings = app(StandingsCalculator::class)->for($tournament);

    expect($standings)->toHaveCount(4);
    foreach ($standings as $row) {
        expect($row->played)->toBe(0);
        expect($row->points)->toBe(0);
        expect($row->goalDifference)->toBe(0);
    }
});

it('counts a home win correctly', function () {
    $tournament = Tournament::factory()->create();
    $home = Team::factory()->for($tournament)->create(['name' => 'Home FC']);
    $away = Team::factory()->for($tournament)->create(['name' => 'Away FC']);

    Fixture::factory()->for($tournament)->played(2, 0)->create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $standings = app(StandingsCalculator::class)->for($tournament);

    $homeRow = $standings->first(fn ($row) => $row->team->id === $home->id);
    $awayRow = $standings->first(fn ($row) => $row->team->id === $away->id);

    expect($homeRow->points)->toBe(3);
    expect($homeRow->won)->toBe(1);
    expect($homeRow->goalsFor)->toBe(2);
    expect($homeRow->goalDifference)->toBe(2);
    expect($awayRow->lost)->toBe(1);
    expect($awayRow->points)->toBe(0);
    expect($awayRow->goalDifference)->toBe(-2);
});

it('counts a draw correctly', function () {
    $tournament = Tournament::factory()->create();
    $a = Team::factory()->for($tournament)->create();
    $b = Team::factory()->for($tournament)->create();

    Fixture::factory()->for($tournament)->played(1, 1)->create([
        'home_team_id' => $a->id,
        'away_team_id' => $b->id,
    ]);

    $standings = app(StandingsCalculator::class)->for($tournament);

    foreach ($standings as $row) {
        expect($row->points)->toBe(1);
        expect($row->drawn)->toBe(1);
        expect($row->goalDifference)->toBe(0);
    }
});

it('sorts by points then goal difference then goals for then name', function () {
    $tournament = Tournament::factory()->create();
    $top = Team::factory()->for($tournament)->create(['name' => 'Top']);
    $mid = Team::factory()->for($tournament)->create(['name' => 'Mid']);
    $low = Team::factory()->for($tournament)->create(['name' => 'Low']);

    Fixture::factory()->for($tournament)->played(3, 0)->create([
        'home_team_id' => $top->id,
        'away_team_id' => $low->id,
    ]);
    Fixture::factory()->for($tournament)->played(1, 0)->create([
        'home_team_id' => $mid->id,
        'away_team_id' => $low->id,
    ]);

    $standings = app(StandingsCalculator::class)->for($tournament);

    expect($standings[0]->team->name)->toBe('Top');
    expect($standings[1]->team->name)->toBe('Mid');
    expect($standings[2]->team->name)->toBe('Low');
});

it('ignores unplayed fixtures', function () {
    $tournament = Tournament::factory()->create();
    $a = Team::factory()->for($tournament)->create();
    $b = Team::factory()->for($tournament)->create();

    Fixture::factory()->for($tournament)->played(2, 0)->create([
        'home_team_id' => $a->id,
        'away_team_id' => $b->id,
    ]);
    Fixture::factory()->for($tournament)->create([
        'home_team_id' => $b->id,
        'away_team_id' => $a->id,
    ]);

    $standings = app(StandingsCalculator::class)->for($tournament);

    $aRow = $standings->first(fn ($row) => $row->team->id === $a->id);
    expect($aRow->played)->toBe(1);
    expect($aRow->points)->toBe(3);
});

it('aggregates home and away appearances for the same team', function () {
    $tournament = Tournament::factory()->create();
    $hero = Team::factory()->for($tournament)->create(['name' => 'Hero']);
    $other = Team::factory()->for($tournament)->create(['name' => 'Other']);
    $third = Team::factory()->for($tournament)->create(['name' => 'Third']);

    Fixture::factory()->for($tournament)->played(2, 0)->create([
        'home_team_id' => $hero->id,
        'away_team_id' => $other->id,
    ]);
    Fixture::factory()->for($tournament)->played(1, 1)->create([
        'home_team_id' => $third->id,
        'away_team_id' => $hero->id,
    ]);

    $standings = app(StandingsCalculator::class)->for($tournament);

    $heroRow = $standings->first(fn ($row) => $row->team->id === $hero->id);
    expect($heroRow->played)->toBe(2);
    expect($heroRow->won)->toBe(1);
    expect($heroRow->drawn)->toBe(1);
    expect($heroRow->points)->toBe(4);
    expect($heroRow->goalsFor)->toBe(3);
    expect($heroRow->goalsAgainst)->toBe(1);
});
