<?php

namespace App\Http\Requests\Assessor;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSimulationReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ASESOR) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['draft', 'submitted'])],
            'recommendation' => [Rule::requiredIf($this->input('status') === 'submitted'), 'nullable', Rule::in([
                'recommended', 'recommended_with_development', 'not_recommended',
            ])],
            'assessment_notes' => ['nullable', 'string', 'max:50000'],
        ];
    }
}
