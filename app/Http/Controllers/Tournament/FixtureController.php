<?php

namespace App\Http\Controllers\Tournament;

use App\Actions\Tournament\GenerateFixtures;
use App\Actions\Tournament\UpdateFixtureResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFixtureRequest;
use App\Models\Fixture;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;

class FixtureController extends Controller
{
    public function store(Tournament $tournament, GenerateFixtures $action): RedirectResponse
    {
        $hadFixtures = $tournament->fixtures()->exists();
        $fixtures = $action($tournament);

        return back()->with(
            'success',
            ($hadFixtures ? 'Fixtures regenerated' : 'Fixtures generated')
                ." ({$fixtures->count()} matches, {$fixtures->max('week')} weeks)."
        );
    }

    public function update(Fixture $fixture, UpdateFixtureRequest $request, UpdateFixtureResult $action): RedirectResponse
    {
        $action(
            $fixture,
            $request->integer('home_goals'),
            $request->integer('away_goals'),
        );

        return back()->with('success', 'Match score updated.');
    }
}
