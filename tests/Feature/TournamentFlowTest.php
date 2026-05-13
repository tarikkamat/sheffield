<?php

use App\Contracts\Simulation\MatchSimulator;
use App\Data\MatchResult;
use App\Enums\TournamentStatus;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;

beforeEach(function () {
    $this->withoutVite();

    $this->app->bind(MatchSimulator::class, fn () => new class implements MatchSimulator
    {
        public function simulate(Team $home, Team $away): MatchResult
        {
            return new MatchResult(homeGoals: 2, awayGoals: 1);
        }
    });
});

function tournamentWithFourTeams(): Tournament
{
    return Tournament::factory()
        ->has(Team::factory()->count(4))
        ->create();
}

it('renders the teams page with seeded teams', function () {
    $tournament = tournamentWithFourTeams();

    $this->get(route('tournaments.show', $tournament))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tournament/Teams')
            ->has('teams', 4)
            ->where('tournament.id', $tournament->id)
        );
});

it('generates fixtures via the store endpoint', function () {
    $tournament = tournamentWithFourTeams();

    $this->post(route('tournaments.fixtures.store', $tournament))
        ->assertRedirect();

    expect($tournament->fixtures()->count())->toBe(12);
});

it('plays the next week via the weeks endpoint', function () {
    $tournament = tournamentWithFourTeams();
    $this->post(route('tournaments.fixtures.store', $tournament));

    $this->post(route('tournaments.weeks.store', $tournament))
        ->assertRedirect();

    $tournament->refresh();
    expect($tournament->current_week)->toBe(1);
    expect($tournament->fixtures()->played()->count())->toBe(2);
});

it('plays all remaining weeks via the play-all endpoint', function () {
    $tournament = tournamentWithFourTeams();
    $this->post(route('tournaments.fixtures.store', $tournament));

    $this->post(route('tournaments.play-all.store', $tournament))
        ->assertRedirect();

    $tournament->refresh();
    expect($tournament->current_week)->toBe(6);
    expect($tournament->status)->toBe(TournamentStatus::Completed);
    expect($tournament->fixtures()->played()->count())->toBe(12);
});

it('resets the tournament via the results endpoint', function () {
    $tournament = tournamentWithFourTeams();
    $this->post(route('tournaments.fixtures.store', $tournament));
    $this->post(route('tournaments.play-all.store', $tournament));

    $this->delete(route('tournaments.results.destroy', $tournament))
        ->assertRedirect();

    $tournament->refresh();
    expect($tournament->current_week)->toBe(0);
    expect($tournament->status)->toBe(TournamentStatus::Pending);
    expect($tournament->fixtures()->played()->count())->toBe(0);
    expect($tournament->fixtures()->count())->toBe(12);
});

it('renders the simulation page with standings', function () {
    $tournament = tournamentWithFourTeams();
    $this->post(route('tournaments.fixtures.store', $tournament));
    $this->post(route('tournaments.play-all.store', $tournament));

    $this->get(route('tournaments.simulation.show', $tournament))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tournament/Simulation')
            ->has('standings', 4)
            ->where('tournament.totalWeeks', 6)
            ->where('currentWeek', 6)
            ->where('showPredictions', true)
            ->has('predictions', 4)
        );
});

it('updates a fixture result via the patch endpoint', function () {
    $tournament = tournamentWithFourTeams();
    $this->post(route('tournaments.fixtures.store', $tournament));

    /** @var Fixture $fixture */
    $fixture = $tournament->fixtures()->first();

    $this->patch(route('fixtures.update', $fixture), [
        'home_goals' => 3,
        'away_goals' => 2,
    ])->assertRedirect();

    $fixture->refresh();
    expect($fixture->home_goals)->toBe(3);
    expect($fixture->away_goals)->toBe(2);
    expect($fixture->isPlayed())->toBeTrue();
});

it('returns 422 when playing a week after the tournament is completed', function () {
    $tournament = tournamentWithFourTeams();
    $this->post(route('tournaments.fixtures.store', $tournament));
    $this->post(route('tournaments.play-all.store', $tournament));

    $this->post(route('tournaments.weeks.store', $tournament))
        ->assertStatus(422);
});

it('validates fixture score updates', function () {
    $tournament = tournamentWithFourTeams();
    $this->post(route('tournaments.fixtures.store', $tournament));
    $fixture = $tournament->fixtures()->first();

    $this->patch(route('fixtures.update', $fixture), [
        'home_goals' => -1,
        'away_goals' => 100,
    ])->assertSessionHasErrors(['home_goals', 'away_goals']);
});
