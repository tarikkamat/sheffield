<?php

use App\Http\Controllers\Tournament\FixtureController;
use App\Http\Controllers\Tournament\PlayAllController;
use App\Http\Controllers\Tournament\ResultController;
use App\Http\Controllers\Tournament\SimulationController;
use App\Http\Controllers\Tournament\TournamentController;
use App\Http\Controllers\Tournament\WeekController;
use App\Models\Tournament;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $tournament = Tournament::query()->orderBy('id')->first();

    if ($tournament === null) {
        abort(404, 'No tournament available. Run database seeders.');
    }

    return redirect()->route('tournaments.show', $tournament);
});

Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])
    ->name('tournaments.show');

Route::post('/tournaments/{tournament}/fixtures', [FixtureController::class, 'store'])
    ->name('tournaments.fixtures.store');

Route::patch('/fixtures/{fixture}', [FixtureController::class, 'update'])
    ->name('fixtures.update');

Route::get('/tournaments/{tournament}/simulation', [SimulationController::class, 'show'])
    ->name('tournaments.simulation.show');

Route::post('/tournaments/{tournament}/weeks', [WeekController::class, 'store'])
    ->name('tournaments.weeks.store');

Route::post('/tournaments/{tournament}/play-all', [PlayAllController::class, 'store'])
    ->name('tournaments.play-all.store');

Route::delete('/tournaments/{tournament}/results', [ResultController::class, 'destroy'])
    ->name('tournaments.results.destroy');
