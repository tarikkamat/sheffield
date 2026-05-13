<?php

use App\Models\Team;
use App\Services\Simulation\SeededRandomizer;
use App\Services\Simulation\WeightedStrengthSimulator;
use Tests\TestCase;

uses(TestCase::class);

function makeTeam(string $name, int $power): Team
{
    return new Team([
        'name' => $name,
        'strength' => $power,
        'attack_power' => $power,
        'defense_power' => $power,
        'goalkeeper_power' => $power,
        'supporter_power' => $power,
    ]);
}

function makeSimulator(int $seed = 42): WeightedStrengthSimulator
{
    return new WeightedStrengthSimulator(
        new SeededRandomizer($seed),
        config('league.simulation'),
    );
}

it('produces goal counts within sane bounds', function () {
    $simulator = makeSimulator();
    $strong = makeTeam('Strong', 90);
    $weak = makeTeam('Weak', 60);

    for ($i = 0; $i < 100; $i++) {
        $result = $simulator->simulate($strong, $weak);
        expect($result->homeGoals)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(9);
        expect($result->awayGoals)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(9);
    }
});

it('lets stronger sides outscore weaker ones across many runs', function () {
    $simulator = makeSimulator();
    $strong = makeTeam('Strong', 95);
    $weak = makeTeam('Weak', 55);

    $strongerWins = 0;
    $iterations = 500;
    for ($i = 0; $i < $iterations; $i++) {
        $result = $simulator->simulate($strong, $weak);
        if ($result->homeGoals > $result->awayGoals) {
            $strongerWins++;
        }
    }

    expect($strongerWins)->toBeGreaterThan($iterations * 0.6);
});

it('keeps weaker side wins possible (non-zero)', function () {
    $simulator = makeSimulator();
    $strong = makeTeam('Strong', 95);
    $weak = makeTeam('Weak', 55);

    $weakerWins = 0;
    for ($i = 0; $i < 1000; $i++) {
        $result = $simulator->simulate($weak, $strong);
        if ($result->homeGoals > $result->awayGoals) {
            $weakerWins++;
        }
    }

    expect($weakerWins)->toBeGreaterThan(0);
});

it('applies a home-advantage bias between equal opponents', function () {
    $simulator = makeSimulator();
    $a = makeTeam('A', 80);
    $b = makeTeam('B', 80);

    $homeWins = 0;
    $awayWins = 0;
    $iterations = 800;
    for ($i = 0; $i < $iterations; $i++) {
        $result = $simulator->simulate($a, $b);
        if ($result->homeGoals > $result->awayGoals) {
            $homeWins++;
        } elseif ($result->homeGoals < $result->awayGoals) {
            $awayWins++;
        }
    }

    expect($homeWins)->toBeGreaterThan($awayWins);
});

it('produces deterministic output for the same seed', function () {
    $home = makeTeam('Home', 80);
    $away = makeTeam('Away', 80);

    $first = (makeSimulator(123))->simulate($home, $away);
    $second = (makeSimulator(123))->simulate($home, $away);

    expect($first->homeGoals)->toBe($second->homeGoals);
    expect($first->awayGoals)->toBe($second->awayGoals);
});
