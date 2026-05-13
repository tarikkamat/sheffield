# Champions League Simulation

A 4-team football league simulator built for the Insider One Champions League case study. Generates a double round-robin fixture list, simulates matches with weighted team attributes, maintains a live league table, and produces championship-probability predictions from week 4 onward.

## Stack

- **Backend:** Laravel 13, PHP 8.3+
- **Frontend:** Inertia.js v3 + Vue 3 + Tailwind v4
- **Routing types:** Laravel Wayfinder (auto-generated TypeScript route helpers)
- **Testing:** Pest 4 (Pest 4 Browser available)
- **Formatter:** Laravel Pint
- **Linter / Type-check:** ESLint 9, `vue-tsc`
- **Bundler:** Vite 8

## Prerequisites

- PHP 8.3+
- Composer 2
- Node 22+
- SQLite (default) or any database supported by Laravel

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build      # or `composer run dev` for an all-in-one dev server
```

`composer run dev` boots `php artisan serve`, the Vite dev server, the queue listener, and `pail` log tailing in one go.

## Domain Model

| Concept | Where it lives |
| --- | --- |
| Tournament (status, current week) | `App\Models\Tournament` + `App\Enums\TournamentStatus` |
| Team (5 power stats) | `App\Models\Team` |
| Fixture (week, scores, played_at) | `App\Models\Fixture` |
| Match status | `App\Enums\MatchStatus` (Pending / Played / Edited) |

### Team power stats

Each team carries five integer stats in `[50, 99]`. These feed the simulator and the predictor.

| Field | Meaning |
| --- | --- |
| `strength` | Overall power (used for ranking / display) |
| `attack_power` | Drives expected goals scored |
| `defense_power` | Reduces opponent's expected goals |
| `goalkeeper_power` | Extra dampener on opponent's expected goals |
| `supporter_power` | Multiplies the home-advantage bonus |

## Architecture

```
app/
├── Actions/Tournament/        Invokable use-cases (single responsibility each)
│   ├── GenerateFixtures.php   Berger circle-method double round-robin
│   ├── PlayWeek.php           Advance one week
│   ├── PlayAllWeeks.php       Run to the end
│   ├── ResetTournament.php    Wipe scores, status → Pending
│   └── UpdateFixtureResult.php Edit a single match score
├── Contracts/Simulation/       Strategy interfaces
│   ├── MatchSimulator.php
│   ├── ChampionshipPredictor.php
│   └── Randomizer.php
├── Services/Simulation/        Concrete strategies
│   ├── WeightedStrengthSimulator.php
│   ├── MonteCarloPredictor.php
│   ├── StandingsCalculator.php
│   ├── LeagueTableAggregator.php   Shared aggregation kernel (DRY)
│   ├── NativeRandomizer.php        Production mt_rand source
│   └── SeededRandomizer.php        Deterministic source for tests
├── Data/                       spatie/laravel-data DTOs
├── Http/
│   ├── Controllers/Tournament/ Inertia controllers (thin)
│   ├── Controllers/Api/        JSON API controllers (thin)
│   └── Requests/               UpdateFixtureRequest validation
├── Providers/SimulationServiceProvider.php   Interface → impl bindings
└── Exceptions/InvalidTournamentStateException.php   422 domain exception
```

### Design patterns applied

- **Strategy** — `MatchSimulator` & `ChampionshipPredictor` interfaces decouple algorithms from callers; the provider binds the chosen implementation.
- **Action / Command** — every domain mutation is an invokable single-method class.
- **DTO / Value Object** — `MatchResult`, `StandingRowData`, `PredictionRowData`, `TeamData`.
- **Query Scope** — `Fixture::played()/pending()/forWeek()`, `Tournament::active()`.
- **Factory** — Eloquent factories + custom seeder for the four Champions League sides.
- **Dependency Inversion** — controllers depend on interfaces; bindings live in `SimulationServiceProvider`.

### SOLID & DRY notes

- **S** — Models contain no business logic. Aggregation, scoring, sorting, simulation, and prediction are each their own class.
- **O** — A new simulator (e.g. `EloMatchSimulator`) is added by implementing the contract; no caller changes.
- **L** — Every implementation honours its contract: `MatchSimulator::simulate()` always returns `MatchResult`; `ChampionshipPredictor::for()` always returns `Collection<PredictionRowData>`.
- **I** — Tiny interfaces (`MatchSimulator`, `ChampionshipPredictor`, `Randomizer`) — no fat "SimulationService" god class.
- **D** — `WeightedStrengthSimulator` depends on `Randomizer` (not `mt_rand`), so tests can inject `SeededRandomizer`. `StandingsCalculator` and `MonteCarloPredictor` both depend on the same `LeagueTableAggregator`.
- **DRY** — Per-team aggregation logic exists exactly once, in `LeagueTableAggregator`. Standings rendering, Monte Carlo simulation, and tie-break comparison all reuse it.

## Match Simulation

`WeightedStrengthSimulator` computes two Poisson lambdas — one per side — and samples goals via Knuth's algorithm. All knobs live in `config/league.php`:

```php
home_lambda = max(
    min_lambda,
    (home.attack_power
       + home.supporter_power * supporter_weight
       + home_advantage
       - away.defense_power * defense_weight
       - away.goalkeeper_power * goalkeeper_weight
    ) / goal_divisor
)
```

The away lambda is symmetric without the home-advantage bonus. Defaults (in `config/league.php`):

| Knob | Default | Role |
| --- | --- | --- |
| `home_advantage` | 5 | Flat bonus to home attack |
| `supporter_weight` | 0.20 | How much supporter_power scales the home bonus |
| `defense_weight` | 0.50 | Strength of defense in suppressing opponent's lambda |
| `goalkeeper_weight` | 0.30 | Extra goalkeeper-driven suppression |
| `goal_divisor` | 30.0 | Tunes the overall scoring rate |
| `min_lambda` | 0.30 | Floor so no team is guaranteed 0 goals |
| `goal_cap` | 9 | Hard ceiling to avoid absurd 12-7 results |
| `points_for_win` / `points_for_draw` | 3 / 1 | League point rules |

The minimum lambda guarantees that the **weaker team's win probability is never zero**.

## Championship Prediction

`MonteCarloPredictor` is a hybrid: mathematics first, simulation second.

1. **Mathematical elimination:** for each team T compute `max_possible(T) = current_points + 3 × remaining_games(T)`. If any rival's *current* points already exceed T's max, T is awarded **0 %**.
2. **Mathematical guarantee:** if a team's *current* points strictly exceed every rival's max, that team is awarded **100 %** and all others 0 %.
3. **Monte Carlo:** otherwise, the remaining fixtures are simulated `iterations` times (`config('league.predictor.iterations')`, default 1000). The same simulator the user sees in "Play Week" is reused via DI.
4. **Normalisation:** rounded percentages are adjusted on the leader so the surviving rows sum to exactly **100.0 %**.

Predictions surface in the UI once `current_week ≥ config('league.predictor.reveal_from_week')` (default **4**).

## Routes

### Inertia (web)

| Method | URI | Action |
| --- | --- | --- |
| GET | `/tournaments/{tournament}` | Teams overview + "Generate Fixtures" |
| POST | `/tournaments/{tournament}/fixtures` | Generate fixtures |
| GET | `/tournaments/{tournament}/simulation` | Live standings, weekly results, predictions |
| POST | `/tournaments/{tournament}/weeks` | Play next week |
| POST | `/tournaments/{tournament}/play-all` | Play remaining weeks |
| DELETE | `/tournaments/{tournament}/results` | Reset all match results |
| PATCH | `/fixtures/{fixture}` | Edit a match score |

### JSON API

| Method | URI | Notes |
| --- | --- | --- |
| GET | `/api/tournaments/{tournament}` | Tournament + teams |
| GET | `/api/tournaments/{tournament}/standings` | League table |
| GET | `/api/tournaments/{tournament}/matches` | All matches, optional `?week=N` filter |
| GET | `/api/tournaments/{tournament}/predictions` | Championship probabilities (empty before week 4) |
| POST | `/api/tournaments/{tournament}/fixtures` | Generate fixtures (201) |
| POST | `/api/tournaments/{tournament}/play-week` | Play next week (422 when finished) |
| POST | `/api/tournaments/{tournament}/play-all` | Play remaining weeks |
| DELETE | `/api/tournaments/{tournament}/results` | Reset |
| PATCH | `/api/matches/{fixture}` | Edit a match score (422 on invalid input) |

All API responses use a `{ "data": ... }` envelope; validation errors follow Laravel's standard `errors` format.

## Frontend

Vue 3 single-file components, Tailwind utility classes, Wayfinder-typed action calls.

| Component | Role |
| --- | --- |
| `pages/Tournament/Teams.vue` | Team roster + Generate Fixtures button |
| `pages/Tournament/Simulation.vue` | Standings, weekly fixtures, predictions, controls |
| `components/EditableScore.vue` | Inline-edit match score with optimistic UX |
| `components/AppButton.vue`, `Table.vue`, `PageHeading.vue` | Generic primitives |

No duplicate match-score widgets, no duplicate league-table renderers — the generic `<Table>` reads typed columns and the `EditableScore` slot.

## Tests

```bash
php artisan test --compact
```

Currently **51 tests / 1 000+ assertions / ~700 ms**.

Coverage highlights:

- `GenerateFixturesTest` — fixture count, no double-bookings, mirrored home/away, idempotency, validation
- `StandingsCalculatorTest` + `StandingsTieBreakTest` — points/GD/GF/wins/name tie-break, 3-pt win, 1-pt draw
- `WeightedStrengthSimulatorTest` — bounded goals, home advantage, deterministic seeding, weaker side can still win
- `MonteCarloPredictorTest` — sum = 100 %, 100 % to guaranteed leader, 0 % to mathematically eliminated team
- `PlayWeekTest` — single-week advance, completion status, 422 on invalid state
- `TournamentFlowTest` — end-to-end happy path through every Inertia endpoint
- `LeagueApiTest` — every JSON endpoint plus error cases (422 for invalid score, 422 for late play-week)

## Useful commands

```bash
vendor/bin/pint --dirty --format agent   # format changed PHP
php artisan wayfinder:generate           # refresh JS route helpers
npm run lint                              # ESLint --fix
npm run types:check                       # vue-tsc strict mode
npm run build                             # production assets
```

## Default seeded teams

| Name | Strength | Att | Def | GK | Sup |
| --- | --- | --- | --- | --- | --- |
| Manchester City | 90 | 92 | 88 | 87 | 90 |
| Liverpool | 88 | 90 | 85 | 86 | 93 |
| Arsenal | 84 | 86 | 83 | 82 | 85 |
| Chelsea | 80 | 82 | 80 | 78 | 84 |

## Deploy

This project is Laravel Cloud-ready and runs on any Laravel-compatible host. Required env vars are the standard `APP_KEY`, `DB_*`, `APP_URL`; for SQLite no extra config is needed beyond `DB_CONNECTION=sqlite`.
