<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class PredictionRowData extends Data
{
    public function __construct(
        public TeamData $team,
        public float $chance,
    ) {}

    /**
     * Flat shape used by the predictions table on the front-end.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->team->id,
            'name' => $this->team->name,
            'chance' => $this->chance,
        ];
    }
}
