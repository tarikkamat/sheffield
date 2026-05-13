<?php

namespace App\Models;

use App\Enums\TournamentStatus;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'current_week', 'status'])]
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    protected $attributes = [
        'current_week' => 0,
        'status' => TournamentStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_week' => 'integer',
            'status' => TournamentStatus::class,
        ];
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [
            TournamentStatus::Pending,
            TournamentStatus::InProgress,
        ]);
    }

    /**
     * Total weeks in a double round-robin season for the current roster size.
     */
    public function totalWeeks(): int
    {
        return max(0, 2 * ($this->teams()->count() - 1));
    }

    public function hasFixtures(): bool
    {
        return $this->fixtures()->exists();
    }

    /**
     * Predictions become visible once the configured reveal week has been played.
     */
    public function shouldRevealPredictions(): bool
    {
        return $this->current_week >= (int) config('league.predictor.reveal_from_week', 4);
    }
}
