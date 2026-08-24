<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateParticipantAccountRequest extends StoreParticipantAccountRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('participant'))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }
}
