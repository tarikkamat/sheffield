<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Match Simulation
    |--------------------------------------------------------------------------
    |
    | Tunable knobs for the weighted-strength match simulator. Lambdas feed
    | a Poisson goal generator. Higher attack and supporter values raise the
    | scoring rate; defense and goalkeeper values dampen the opponent's rate.
    |
    */
    'simulation' => [
        'home_advantage' => 5,
        'supporter_weight' => 0.20,
        'defense_weight' => 0.50,
        'goalkeeper_weight' => 0.30,
        'goal_divisor' => 30.0,
        'min_lambda' => 0.30,
        'goal_cap' => 9,
        'points_for_win' => 3,
        'points_for_draw' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Championship Predictor
    |--------------------------------------------------------------------------
    |
    | Monte Carlo iteration count and the week at which predictions start
    | showing in the UI. Lowering iterations speeds tests; raising them
    | improves probability resolution.
    |
    */
    'predictor' => [
        'iterations' => 1000,
        'reveal_from_week' => 4,
    ],

];
