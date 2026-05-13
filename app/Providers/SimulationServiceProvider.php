<?php

namespace App\Providers;

use App\Contracts\Simulation\ChampionshipPredictor;
use App\Contracts\Simulation\MatchSimulator;
use App\Contracts\Simulation\Randomizer;
use App\Services\Simulation\MonteCarloPredictor;
use App\Services\Simulation\NativeRandomizer;
use App\Services\Simulation\WeightedStrengthSimulator;
use Illuminate\Support\ServiceProvider;

class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Randomizer::class, NativeRandomizer::class);

        $this->app->bind(MatchSimulator::class, function ($app) {
            return new WeightedStrengthSimulator(
                $app->make(Randomizer::class),
                config('league.simulation'),
            );
        });

        $this->app->bind(ChampionshipPredictor::class, MonteCarloPredictor::class);
    }
}
