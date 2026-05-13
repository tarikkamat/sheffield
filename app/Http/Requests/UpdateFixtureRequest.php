<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFixtureRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'home_goals' => ['required', 'integer', 'min:0', 'max:20'],
            'away_goals' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }
}
