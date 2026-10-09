<?php

namespace App\Http\Requests\Admin;

use App\Models\AssessmentParticipant;
use App\Models\SimulationScenario;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentProgramSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'participant_ids' => ['array'],
            'participant_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', User::ROLE_PESERTA_ASSESSMENT)],
            'participant_categories' => ['array'],
            'participant_categories.*' => ['nullable', Rule::in(array_keys(AssessmentParticipant::CATEGORIES))],
            'participant_simulation_three_choices' => ['array'],
            'participant_simulation_three_choices.*' => ['nullable', Rule::in(array_keys(SimulationScenario::SIMULATION_THREE_PACKAGES + SimulationScenario::LEGACY_SIMULATION_THREE_PACKAGES))],
            'assessor_ids' => ['required', 'array', 'min:1'],
            'assessor_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('role', User::ROLE_ASESOR)],
        ];
    }

    public function messages(): array
    {
        return [
            'assessor_ids.required' => 'Pilih minimal satu asesor untuk program ini.',
            'assessor_ids.min' => 'Pilih minimal satu asesor untuk program ini.',
        ];
    }
}
