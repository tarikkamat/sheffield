<?php

namespace App\Contracts\Simulation;

use App\Data\MatchResult;
use App\Models\Team;

interface MatchSimulator
{
    public function simulate(Team $home, Team $away): MatchResult;
}
