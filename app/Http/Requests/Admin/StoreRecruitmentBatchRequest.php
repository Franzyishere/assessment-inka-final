<?php

namespace App\Http\Requests\Admin;

use App\Models\RecruitmentBatch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecruitmentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(RecruitmentBatch::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return ['ends_at.after' => 'Waktu selesai harus setelah waktu mulai.'];
    }
}
