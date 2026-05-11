<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::inertia('/tournament/teams', 'Tournament/Teams', [
    'teams' => [
        ['id' => 1, 'name' => 'Liverpool'],
        ['id' => 2, 'name' => 'Manchester City'],
        ['id' => 3, 'name' => 'Chelsea'],
        ['id' => 4, 'name' => 'Arsenal'],
    ],
])->name('tournament.teams');

Route::inertia('/tournament/simulation', 'Tournament/Simulation', [
    'standings' => [
        ['id' => 1, 'name' => 'Liverpool', 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goal_difference' => 0],
        ['id' => 2, 'name' => 'Manchester City', 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goal_difference' => 0],
        ['id' => 3, 'name' => 'Chelsea', 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goal_difference' => 0],
        ['id' => 4, 'name' => 'Arsenal', 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goal_difference' => 0],
    ],
    'currentWeek' => 1,
    'fixtures' => [
        ['id' => 1, 'home' => 'Liverpool', 'away' => 'Arsenal'],
        ['id' => 2, 'home' => 'Manchester City', 'away' => 'Chelsea'],
    ],
    'predictions' => [
        ['id' => 1, 'name' => 'Liverpool', 'chance' => 0],
        ['id' => 2, 'name' => 'Manchester City', 'chance' => 0],
        ['id' => 3, 'name' => 'Chelsea', 'chance' => 0],
        ['id' => 4, 'name' => 'Arsenal', 'chance' => 0],
    ],
])->name('tournament.simulation');
