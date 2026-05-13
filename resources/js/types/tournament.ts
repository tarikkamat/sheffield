export type TournamentStatus = 'pending' | 'in_progress' | 'completed';

export type Tournament = {
    id: number;
    name: string | null;
    status: TournamentStatus;
    currentWeek?: number;
    totalWeeks?: number;
    hasFixtures?: boolean;
};

export type Team = {
    id: number;
    name: string;
};

export type Standing = {
    id: number;
    name: string;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    goals_for: number;
    goals_against: number;
    goal_difference: number;
    points: number;
};

export type Fixture = {
    id: number;
    week?: number;
    home: string;
    away: string;
    home_goals: number | null;
    away_goals: number | null;
    is_played: boolean;
    score: string;
};

export type Prediction = {
    id: number;
    name: string;
    chance: number;
};
