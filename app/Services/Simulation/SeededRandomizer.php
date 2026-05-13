<?php

namespace App\Services\Simulation;

use App\Contracts\Simulation\Randomizer;

/**
 * Deterministic randomizer for tests — emits the same sequence for a seed.
 */
final class SeededRandomizer implements Randomizer
{
    public function __construct(int $seed)
    {
        mt_srand($seed);
    }

    public function uniform(): float
    {
        return mt_rand() / mt_getrandmax();
    }

    public function int(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }
}
