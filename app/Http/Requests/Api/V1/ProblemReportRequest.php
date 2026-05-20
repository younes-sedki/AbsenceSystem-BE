<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProblemReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sujet' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
