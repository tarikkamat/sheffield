<?php

namespace App\Models;

use Database\Factories\FixtureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tournament_id', 'week', 'home_team_id', 'away_team_id', 'home_goals', 'away_goals', 'played_at'])]
class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week' => 'integer',
            'home_goals' => 'integer',
            'away_goals' => 'integer',
            'played_at' => 'datetime',
        ];
    }

    public function isPlayed(): bool
    {
        return $this->played_at !== null;
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePlayed(Builder $query): void
    {
        $query->whereNotNull('played_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('played_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForWeek(Builder $query, int $week): void
    {
        $query->where('week', $week);
    }
}
