<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SoumettreSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'absences' => ['required', 'array'],
            'absences.*.etudiant_id' => ['required', 'exists:users,id'],
            'absences.*.present' => ['required', 'boolean'],
        ];
    }
}
