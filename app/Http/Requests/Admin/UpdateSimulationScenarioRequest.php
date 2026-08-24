<?php

namespace App\Http\Requests\Admin;

class UpdateSimulationScenarioRequest extends StoreSimulationScenarioRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
