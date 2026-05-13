<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tournament\GenerateFixtures;
use App\Actions\Tournament\PlayAllWeeks;
use App\Actions\Tournament\PlayWeek;
use App\Actions\Tournament\ResetTournament;
use App\Actions\Tournament\UpdateFixtureResult;
use App\Contracts\Simulation\ChampionshipPredictor;
use App\Data\MatchData;
use App\Data\TeamData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFixtureRequest;
use App\Models\Fixture;
use App\Models\Tournament;
use App\Services\Simulation\StandingsCalculator;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    public function show(Tournament $tournament): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->value,
                'current_week' => $tournament->current_week,
                'total_weeks' => $tournament->totalWeeks(),
                'has_fixtures' => $tournament->hasFixtures(),
                'teams' => $tournament->teams()->orderBy('id')->get()
                    ->map(fn ($team) => TeamData::fromModelWithProfile($team)),
            ],
        ]);
    }

    public function standings(Tournament $tournament, StandingsCalculator $standings): JsonResponse
    {
        return response()->json([
            'data' => $standings->for($tournament),
        ]);
    }

    public function matches(Tournament $tournament): JsonResponse
    {
        $query = $tournament->fixtures()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('week')
            ->orderBy('id');

        if (request()->filled('week')) {
            $query->forWeek((int) request('week'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (Fixture $f) => MatchData::fromModel($f)),
        ]);
    }

    public function predictions(Tournament $tournament, ChampionshipPredictor $predictor): JsonResponse
    {
        if (! $tournament->shouldRevealPredictions()) {
            $revealFromWeek = (int) config('league.predictor.reveal_from_week', 4);

            return response()->json([
                'data' => [],
                'message' => "Predictions become available from week {$revealFromWeek}.",
            ]);
        }

        return response()->json([
            'data' => $predictor->for($tournament),
        ]);
    }

    public function generateFixtures(Tournament $tournament, GenerateFixtures $action): JsonResponse
    {
        $action($tournament);

        return response()->json([
            'data' => $tournament->fixtures()
                ->with(['homeTeam', 'awayTeam'])
                ->orderBy('week')->orderBy('id')
                ->get()
                ->map(fn (Fixture $f) => MatchData::fromModel($f)),
        ], 201);
    }

    public function playWeek(Tournament $tournament, PlayWeek $action): JsonResponse
    {
        $played = $action($tournament);

        return response()->json([
            'data' => [
                'current_week' => $tournament->fresh()->current_week,
                'status' => $tournament->fresh()->status->value,
                'matches' => $played->load(['homeTeam', 'awayTeam'])
                    ->map(fn (Fixture $f) => MatchData::fromModel($f)),
            ],
        ]);
    }

    public function playAll(Tournament $tournament, PlayAllWeeks $action): JsonResponse
    {
        $action($tournament);

        return response()->json([
            'data' => [
                'current_week' => $tournament->fresh()->current_week,
                'status' => $tournament->fresh()->status->value,
            ],
        ]);
    }

    public function reset(Tournament $tournament, ResetTournament $action): JsonResponse
    {
        $action($tournament);

        return response()->json([
            'data' => [
                'current_week' => $tournament->fresh()->current_week,
                'status' => $tournament->fresh()->status->value,
            ],
        ]);
    }

    public function updateMatch(
        Fixture $fixture,
        UpdateFixtureRequest $request,
        UpdateFixtureResult $action,
    ): JsonResponse {
        $updated = $action(
            $fixture,
            $request->integer('home_goals'),
            $request->integer('away_goals'),
        );

        return response()->json([
            'data' => MatchData::fromModel($updated->load(['homeTeam', 'awayTeam'])),
        ]);
    }
}
