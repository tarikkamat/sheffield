<?php

namespace App\Contracts\Simulation;

use App\Data\PredictionRowData;
use App\Models\Tournament;
use Illuminate\Support\Collection;

interface ChampionshipPredictor
{
    /**
     * @return Collection<int, PredictionRowData>
     */
    public function for(Tournament $tournament): Collection;
}
