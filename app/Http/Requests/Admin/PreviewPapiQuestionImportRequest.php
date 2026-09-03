<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PreviewPapiQuestionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return ['question_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']];
    }

    public function messages(): array
    {
        return [
            'question_file.required' => 'Pilih file soal terlebih dahulu.',
            'question_file.mimes' => 'File soal harus berformat XLSX, XLS, atau CSV.',
            'question_file.max' => 'Ukuran file soal maksimal 5 MB.',
        ];
    }
}
