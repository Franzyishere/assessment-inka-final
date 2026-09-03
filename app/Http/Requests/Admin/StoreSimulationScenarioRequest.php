<?php

namespace App\Http\Requests\Admin;

use App\Models\AssessmentParticipant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSimulationScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'simulation_type_id' => ['required', 'integer', 'exists:simulation_types,id'],
            'assessment_category' => [
                'nullable',
                Rule::in(array_keys(AssessmentParticipant::CATEGORIES)),
            ],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'material_pages' => ['nullable', 'array', 'max:20'],
            'material_pages.*.id' => ['nullable', 'integer'],
            'material_pages.*.title' => ['nullable', 'string', 'max:255'],
            'material_pages.*.content' => ['nullable', 'string'],
            'material_pages.*.attachment' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
            'material_pages.*.is_required' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration_minutes.min' => 'Durasi minimal adalah 1 menit.',
            'duration_minutes.max' => 'Durasi maksimal adalah 1.440 menit.',
            'material_pages.max' => 'Maksimal 20 materi PDF dalam satu paket.',
            'material_pages.*.attachment.mimes' => 'Setiap materi harus berupa file PDF.',
            'material_pages.*.attachment.max' => 'Ukuran setiap PDF maksimal 50 MB.',
        ];
    }
}
