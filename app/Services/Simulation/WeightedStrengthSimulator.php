<?php

namespace App\Services\Simulation;

use App\Contracts\Simulation\MatchSimulator;
use App\Contracts\Simulation\Randomizer;
use App\Data\MatchResult;
use App\Models\Team;

final class WeightedStrengthSimulator implements MatchSimulator
{
    public function __construct(
        private Randomizer $randomizer,
        /** @var array<string, float|int> */
        private array $config,
    ) {}

    public function simulate(Team $home, Team $away): MatchResult
    {
        return new MatchResult(
            homeGoals: $this->samplePoisson($this->homeLambda($home, $away)),
            awayGoals: $this->samplePoisson($this->awayLambda($home, $away)),
        );
    }

    private function homeLambda(Team $home, Team $away): float
    {
        $attack = $home->attack_power
            + $home->supporter_power * (float) $this->config['supporter_weight']
            + (float) $this->config['home_advantage'];

        $defense = $away->defense_power * (float) $this->config['defense_weight']
            + $away->goalkeeper_power * (float) $this->config['goalkeeper_weight'];

        return max(
            (float) $this->config['min_lambda'],
            ($attack - $defense) / (float) $this->config['goal_divisor'],
        );
    }

    private function awayLambda(Team $home, Team $away): float
    {
        $defense = $home->defense_power * (float) $this->config['defense_weight']
            + $home->goalkeeper_power * (float) $this->config['goalkeeper_weight'];

        return max(
            (float) $this->config['min_lambda'],
            ($away->attack_power - $defense) / (float) $this->config['goal_divisor'],
        );
    }

    /**
     * Knuth's algorithm for sampling from a Poisson distribution.
     */
    private function samplePoisson(float $lambda): int
    {
        $threshold = exp(-$lambda);
        $product = 1.0;
        $count = 0;

        do {
            $count++;
            $product *= $this->randomizer->uniform();
        } while ($product > $threshold);

        return min($count - 1, (int) $this->config['goal_cap']);
    }
}
