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
        $session = $this->route('session');
        $isFinalObservation = $this->input('status') === 'submitted'
            && $session?->programSimulation?->scenario?->type?->delivery_mode === 'assessor_observation';

        return [
            'status' => ['required', Rule::in(['draft', 'submitted'])],
            'recommendation' => [Rule::requiredIf($this->input('status') === 'submitted'), 'nullable', Rule::in([
                'recommended', 'recommended_with_development', 'not_recommended',
            ])],
            'assessment_notes' => [Rule::requiredIf($isFinalObservation), 'nullable', 'string', 'max:50000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assessment_notes.required' => 'Hasil observasi LGD wajib diisi sebelum penilaian difinalisasi.',
        ];
    }
}
