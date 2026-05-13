<?php

namespace App\Contracts\Simulation;

interface Randomizer
{
    /**
     * Return a uniformly distributed float in the half-open interval [0, 1).
     */
    public function uniform(): float;

    /**
     * Return an integer in the inclusive range [$min, $max].
     */
    public function int(int $min, int $max): int;
}
