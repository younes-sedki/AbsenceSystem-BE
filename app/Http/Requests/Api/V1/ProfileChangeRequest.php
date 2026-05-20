<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProfileChangeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'champ' => ['required', 'in:telephone,email,etablissement,filiere'],
            'nouvelle_valeur' => ['required', 'string', 'max:255'],
        ];
    }
}
