<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessorAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'assessor_ids' => ['required', 'array', 'min:1'],
            'assessor_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('role', User::ROLE_ASESOR)],
        ];
    }

    public function messages(): array
    {
        return [
            'assessor_ids.required' => 'Tim asesor program tidak boleh kosong.',
            'assessor_ids.min' => 'Tim asesor program tidak boleh kosong.',
        ];
    }
}
