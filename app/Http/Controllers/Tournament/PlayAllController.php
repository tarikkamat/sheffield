<?php

namespace App\Http\Controllers\Tournament;

use App\Actions\Tournament\PlayAllWeeks;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;

class PlayAllController extends Controller
{
    public function store(Tournament $tournament, PlayAllWeeks $action): RedirectResponse
    {
        $action($tournament);

        return back()->with('success', 'Season completed.');
    }
}
