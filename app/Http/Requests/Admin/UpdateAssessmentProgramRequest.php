<?php

namespace App\Http\Requests\Admin;

class UpdateAssessmentProgramRequest extends StoreAssessmentProgramRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
