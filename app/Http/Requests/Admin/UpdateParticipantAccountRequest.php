<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateParticipantAccountRequest extends StoreParticipantAccountRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('participant'))],
            'employee_number' => ['nullable', 'string', 'max:100', Rule::unique('users')->ignore($this->route('participant'))],
        ];
    }
}
