<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tournament_id',
    'name',
    'strength',
    'attack_power',
    'defense_power',
    'goalkeeper_power',
    'supporter_power',
])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $attributes = [
        'strength' => 70,
        'attack_power' => 70,
        'defense_power' => 70,
        'goalkeeper_power' => 70,
        'supporter_power' => 70,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'strength' => 'integer',
            'attack_power' => 'integer',
            'defense_power' => 'integer',
            'goalkeeper_power' => 'integer',
            'supporter_power' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function homeFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function awayFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }
}
