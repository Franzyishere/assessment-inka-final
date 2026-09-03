<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePapiVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('psychological_test_versions', 'version')->where('psychological_test_id', $this->route('psychologicalTest')?->id)],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
