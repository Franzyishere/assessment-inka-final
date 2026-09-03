<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PreviewRecruitmentParticipantImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'participant_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'participant_file.required' => 'Pilih file peserta terlebih dahulu.',
            'participant_file.mimes' => 'File peserta harus berformat XLSX, XLS, atau CSV.',
            'participant_file.max' => 'Ukuran file peserta maksimal 5 MB.',
        ];
    }
}
