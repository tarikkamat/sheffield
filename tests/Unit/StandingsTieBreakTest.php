<?php

use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Simulation\StandingsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('breaks ties by points, then GD, then GF, then wins, then name', function () {
    $tournament = Tournament::factory()->create();
    $byPoints = Team::factory()->for($tournament)->create(['name' => 'A-Points']);
    $byGd = Team::factory()->for($tournament)->create(['name' => 'B-GD']);
    $byGf = Team::factory()->for($tournament)->create(['name' => 'C-GF']);
    $byWins = Team::factory()->for($tournament)->create(['name' => 'D-Wins']);

    // All four end at 3 points, but ranked differently by subsequent criteria.
    // A-Points: 1 win 5-0 (GD +5, GF 5, wins 1)
    Fixture::factory()->for($tournament)->played(5, 0)->create([
        'home_team_id' => $byPoints->id,
        'away_team_id' => $byGd->id,
    ]);
    Fixture::factory()->for($tournament)->played(0, 5)->create([
        'home_team_id' => $byGd->id,
        'away_team_id' => $byPoints->id,
    ]);

    // B-GD: 1 win 3-0 (GD +3, GF 3, wins 1)
    Fixture::factory()->for($tournament)->played(3, 0)->create([
        'home_team_id' => $byGd->id,
        'away_team_id' => $byGf->id,
    ]);
    Fixture::factory()->for($tournament)->played(0, 3)->create([
        'home_team_id' => $byGf->id,
        'away_team_id' => $byGd->id,
    ]);

    // C-GF: 1 win 2-0 then draw 0-0 (GD +2, GF 2, wins 1, pts 4)
    Fixture::factory()->for($tournament)->played(2, 0)->create([
        'home_team_id' => $byGf->id,
        'away_team_id' => $byWins->id,
    ]);
    Fixture::factory()->for($tournament)->played(0, 2)->create([
        'home_team_id' => $byWins->id,
        'away_team_id' => $byGf->id,
    ]);

    $standings = app(StandingsCalculator::class)->for($tournament);

    expect($standings[0]->team->name)->toBe('A-Points');
    expect($standings[0]->goalDifference)->toBe(10);
});

it('awards 3 points for a win and 1 for a draw', function () {
    $tournament = Tournament::factory()->create();
    $a = Team::factory()->for($tournament)->create(['name' => 'A']);
    $b = Team::factory()->for($tournament)->create(['name' => 'B']);
    $c = Team::factory()->for($tournament)->create(['name' => 'C']);

    Fixture::factory()->for($tournament)->played(1, 0)->create([
        'home_team_id' => $a->id,
        'away_team_id' => $b->id,
    ]);
    Fixture::factory()->for($tournament)->played(1, 1)->create([
        'home_team_id' => $a->id,
        'away_team_id' => $c->id,
    ]);

    $standings = app(StandingsCalculator::class)->for($tournament);
    $aRow = $standings->first(fn ($r) => $r->team->id === $a->id);
    $bRow = $standings->first(fn ($r) => $r->team->id === $b->id);
    $cRow = $standings->first(fn ($r) => $r->team->id === $c->id);

    expect($aRow->points)->toBe(4);
    expect($aRow->won)->toBe(1);
    expect($aRow->drawn)->toBe(1);
    expect($bRow->points)->toBe(0);
    expect($cRow->points)->toBe(1);
});
