<?php

use App\Contracts\Simulation\MatchSimulator;
use App\Data\MatchResult;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(MatchSimulator::class, fn () => new class implements MatchSimulator
    {
        public function simulate(Team $home, Team $away): MatchResult
        {
            return new MatchResult(homeGoals: 2, awayGoals: 1);
        }
    });
});

function apiTournament(): Tournament
{
    return Tournament::factory()
        ->has(Team::factory()->count(4))
        ->create();
}

it('returns tournament metadata as JSON', function () {
    $tournament = apiTournament();

    $this->getJson(route('api.tournaments.show', $tournament))
        ->assertOk()
        ->assertJsonPath('data.id', $tournament->id)
        ->assertJsonPath('data.total_weeks', 6)
        ->assertJsonCount(4, 'data.teams');
});

it('exposes standings via JSON API', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));
    $this->postJson(route('api.tournaments.play-all', $tournament));

    $this->getJson(route('api.tournaments.standings', $tournament))
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonStructure([
            'data' => [
                ['team' => ['id', 'name'], 'points', 'goalDifference', 'won'],
            ],
        ]);
});

it('lists matches and supports per-week filtering', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));

    $this->getJson(route('api.tournaments.matches', $tournament))
        ->assertOk()
        ->assertJsonCount(12, 'data');

    $this->getJson(route('api.tournaments.matches', $tournament).'?week=1')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('generates fixtures via POST /fixtures', function () {
    $tournament = apiTournament();

    $this->postJson(route('api.tournaments.fixtures', $tournament))
        ->assertCreated()
        ->assertJsonCount(12, 'data');
});

it('plays a single week via POST /play-week', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));

    $this->postJson(route('api.tournaments.play-week', $tournament))
        ->assertOk()
        ->assertJsonPath('data.current_week', 1)
        ->assertJsonCount(2, 'data.matches');
});

it('returns 422 from /play-week after the season is over', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));
    $this->postJson(route('api.tournaments.play-all', $tournament));

    $this->postJson(route('api.tournaments.play-week', $tournament))
        ->assertStatus(422);
});

it('hides predictions before the reveal week and shows them after', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));

    $this->getJson(route('api.tournaments.predictions', $tournament))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    for ($i = 0; $i < 4; $i++) {
        $this->postJson(route('api.tournaments.play-week', $tournament));
    }

    $this->getJson(route('api.tournaments.predictions', $tournament))
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('updates a match score via PATCH /api/matches/{fixture}', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));
    $fixture = $tournament->fixtures()->first();

    $this->patchJson(route('api.matches.update', $fixture), [
        'home_goals' => 4,
        'away_goals' => 1,
    ])->assertOk()
        ->assertJsonPath('data.home_goals', 4)
        ->assertJsonPath('data.away_goals', 1);
});

it('rejects invalid scores with 422', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));
    $fixture = $tournament->fixtures()->first();

    $this->patchJson(route('api.matches.update', $fixture), [
        'home_goals' => -1,
        'away_goals' => 'foo',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['home_goals', 'away_goals']);
});

it('resets a tournament via DELETE /results', function () {
    $tournament = apiTournament();
    $this->postJson(route('api.tournaments.fixtures', $tournament));
    $this->postJson(route('api.tournaments.play-all', $tournament));

    $this->deleteJson(route('api.tournaments.reset', $tournament))
        ->assertOk()
        ->assertJsonPath('data.current_week', 0)
        ->assertJsonPath('data.status', 'pending');
});
