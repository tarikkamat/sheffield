<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    /**
     * Default Champions League roster used to bootstrap a new tournament.
     *
     * @var list<array{name: string, strength: int, attack_power: int, defense_power: int, goalkeeper_power: int, supporter_power: int}>
     */
    private const DEFAULT_TEAMS = [
        [
            'name' => 'Manchester City',
            'strength' => 90,
            'attack_power' => 92,
            'defense_power' => 88,
            'goalkeeper_power' => 87,
            'supporter_power' => 90,
        ],
        [
            'name' => 'Liverpool',
            'strength' => 88,
            'attack_power' => 90,
            'defense_power' => 85,
            'goalkeeper_power' => 86,
            'supporter_power' => 93,
        ],
        [
            'name' => 'Arsenal',
            'strength' => 84,
            'attack_power' => 86,
            'defense_power' => 83,
            'goalkeeper_power' => 82,
            'supporter_power' => 85,
        ],
        [
            'name' => 'Chelsea',
            'strength' => 80,
            'attack_power' => 82,
            'defense_power' => 80,
            'goalkeeper_power' => 78,
            'supporter_power' => 84,
        ],
    ];

    public function run(): void
    {
        $tournament = Tournament::factory()->create([
            'name' => 'Champions League',
        ]);

        foreach (self::DEFAULT_TEAMS as $team) {
            Team::factory()
                ->for($tournament)
                ->create($team);
        }
    }
}
