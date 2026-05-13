<?php

use App\Http\Controllers\Api\LeagueController;
use Illuminate\Support\Facades\Route;

Route::prefix('tournaments/{tournament}')->group(function () {
    Route::get('/', [LeagueController::class, 'show'])->name('api.tournaments.show');
    Route::get('/standings', [LeagueController::class, 'standings'])->name('api.tournaments.standings');
    Route::get('/matches', [LeagueController::class, 'matches'])->name('api.tournaments.matches');
    Route::get('/predictions', [LeagueController::class, 'predictions'])->name('api.tournaments.predictions');

    Route::post('/fixtures', [LeagueController::class, 'generateFixtures'])->name('api.tournaments.fixtures');
    Route::post('/play-week', [LeagueController::class, 'playWeek'])->name('api.tournaments.play-week');
    Route::post('/play-all', [LeagueController::class, 'playAll'])->name('api.tournaments.play-all');
    Route::delete('/results', [LeagueController::class, 'reset'])->name('api.tournaments.reset');
});

Route::patch('/matches/{fixture}', [LeagueController::class, 'updateMatch'])->name('api.matches.update');
