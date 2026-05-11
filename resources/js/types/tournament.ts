export type Team = {
    id: number | string;
    name: string;
};

export type Standing = {
    id: number | string;
    name: string;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    goal_difference: number;
};

export type Fixture = {
    id: number | string;
    home: string;
    away: string;
};

export type Prediction = {
    id: number | string;
    name: string;
    chance: number;
};
