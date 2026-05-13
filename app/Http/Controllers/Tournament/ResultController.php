<?php

namespace App\Http\Controllers\Tournament;

use App\Actions\Tournament\ResetTournament;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;

class ResultController extends Controller
{
    public function destroy(Tournament $tournament, ResetTournament $action): RedirectResponse
    {
        $action($tournament);

        return back()->with('success', 'Tournament reset.');
    }
}
