<?php

use App\Models\Team;
use App\Models\Tournament;

test('the root redirects to the first tournament', function () {
    $this->withoutVite();
    $tournament = Tournament::factory()->has(Team::factory()->count(4))->create();

    $this->get('/')
        ->assertRedirect(route('tournaments.show', $tournament));
});
