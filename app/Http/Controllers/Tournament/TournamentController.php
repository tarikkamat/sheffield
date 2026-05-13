<?php

namespace App\Http\Controllers\Tournament;

use App\Data\TeamData;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function show(Tournament $tournament): Response
    {
        return Inertia::render('Tournament/Teams', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->value,
                'currentWeek' => $tournament->current_week,
                'hasFixtures' => $tournament->hasFixtures(),
            ],
            'teams' => TeamData::collect($tournament->teams()->orderBy('id')->get()),
        ]);
    }
}
