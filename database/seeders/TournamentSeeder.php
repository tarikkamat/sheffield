<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    /**
     * Default Premier League roster used to bootstrap a new tournament.
     *
     * @var list<array{name: string, strength: int}>
     */
    private const DEFAULT_TEAMS = [
        ['name' => 'Liverpool', 'strength' => 88],
        ['name' => 'Manchester City', 'strength' => 90],
        ['name' => 'Chelsea', 'strength' => 80],
        ['name' => 'Arsenal', 'strength' => 84],
    ];

    public function run(): void
    {
        $tournament = Tournament::factory()->create([
            'name' => 'Premier League',
        ]);

        foreach (self::DEFAULT_TEAMS as $team) {
            Team::factory()
                ->for($tournament)
                ->create($team);
        }
    }
}
