<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Pending = 'pending';
    case Played = 'played';
    case Edited = 'edited';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Played => 'Played',
            self::Edited => 'Edited',
        };
    }
}
