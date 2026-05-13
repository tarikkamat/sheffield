<?php

namespace App\Http\Controllers\Tournament;

use App\Actions\Tournament\PlayWeek;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;

class WeekController extends Controller
{
    public function store(Tournament $tournament, PlayWeek $action): RedirectResponse
    {
        $played = $action($tournament);
        $week = $played->first()?->week;

        return back()->with('success', "Week {$week} played.");
    }
}
