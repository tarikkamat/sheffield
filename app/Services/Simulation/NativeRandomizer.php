<?php

namespace App\Services\Simulation;

use App\Contracts\Simulation\Randomizer;

final class NativeRandomizer implements Randomizer
{
    public function uniform(): float
    {
        return mt_rand() / mt_getrandmax();
    }

    public function int(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }
}
