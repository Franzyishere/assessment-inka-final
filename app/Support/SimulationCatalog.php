<?php

namespace App\Support;

use App\Models\AssessmentProgram;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SimulationCatalog
{
    public static function forProgram(AssessmentProgram $program, int $userId): Collection
    {
        $catalog = self::ensure($userId);
        $hasStarted = $program->simulations()->whereHas('scenario.type', fn ($query) => $query->where('delivery_mode', 'case_response'))
            ->whereHas('sessions')->exists();

        if ($hasStarted) {
            return $catalog->reject(fn ($scenario) => $scenario->type->delivery_mode === 'case_response')
                ->concat($program->simulations->filter(fn ($simulation) => $simulation->scenario->type->delivery_mode === 'case_response')->pluck('scenario'))
                ->sortBy(fn ($scenario) => $scenario->type->sequence)->values();
        }

        return $catalog;
    }

    public static function ensure(int $userId): Collection
    {
        return DB::transaction(function () use ($userId): Collection {
            $types = SimulationType::query()->where('is_active', true)->orderBy('sequence')->get();
            $catalog = collect();

            foreach ($types as $type) {
                $packages = $type->code === SimulationType::CRITICAL_INCIDENT
                    ? array_keys(SimulationScenario::SIMULATION_THREE_PACKAGES)
                    : [null];

                foreach ($packages as $package) {
                    $scenario = SimulationScenario::query()
                        ->where('simulation_type_id', $type->id)
                        ->where('simulation_package', $package)
                        ->orderBy('id', array_key_exists((string) $package, SimulationScenario::SIMULATION_THREE_PACKAGES) ? 'desc' : 'asc')
                        ->first();

                    if (! $scenario) {
                        $scenario = SimulationScenario::create([
                            'simulation_type_id' => $type->id,
                            'assessment_category' => null,
                            'simulation_package' => $package,
                            'code' => 'SYSTEM-'.strtoupper($type->code).($package ? '-'.strtoupper($package) : ''),
                            'title' => $type->name,
                            'duration_minutes' => in_array($type->delivery_mode, ['multi_page_response', 'case_response'], true) ? 60 : null,
                            'status' => 'active',
                            'created_by' => $userId,
                        ]);
                    } elseif ($scenario->status !== 'active') {
                        $scenario->update(['status' => 'active']);
                    }
                    if ($type->delivery_mode === 'assessor_observation' && ! $scenario->duration_minutes) {
                        $scenario->update(['duration_minutes' => 60]);
                    }

                    $catalog->push($scenario->loadMissing('type'));
                }
            }

            return $catalog->sortBy(fn ($scenario) => [$scenario->type->sequence, array_search($scenario->simulation_package, array_keys(SimulationScenario::SIMULATION_THREE_PACKAGES), true) ?: 0])->values();
        });
    }
}
