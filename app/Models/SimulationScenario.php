<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationScenario extends Model
{
    use HasFactory;

    public const SIMULATION_THREE_PACKAGES = [
        'ci_1' => ['label' => 'Critical Incident 1', 'audience' => 'Golongan I ke II, Spesialis Pratama, dan SPV'],
        'ci_2' => ['label' => 'Critical Incident 2', 'audience' => 'Golongan II ke III dan Spesialis Muda'],
        'ci_3' => ['label' => 'Critical Incident 3', 'audience' => 'Golongan III ke IV atau pilihan Spesialis Madya'],
        'in_tray_1' => ['label' => 'In-Tray 1', 'audience' => 'Promosi M'],
        'in_tray_2' => ['label' => 'In-Tray 2', 'audience' => 'Promosi SM'],
        'in_tray_3' => ['label' => 'In-Tray 3', 'audience' => 'Pilihan Spesialis Madya'],
    ];

    protected $fillable = ['simulation_type_id', 'assessment_category', 'simulation_package', 'code', 'title', 'description', 'participant_instructions', 'assessor_guidance', 'duration_minutes', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['duration_minutes' => 'integer'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(SimulationType::class, 'simulation_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function materialPages(): HasMany
    {
        return $this->hasMany(SimulationMaterialPage::class)->orderBy('page_order');
    }

    public function programSimulations(): HasMany
    {
        return $this->hasMany(AssessmentProgramSimulation::class);
    }

    public function simulationThreeTrack(): ?string
    {
        if ($this->type?->delivery_mode !== 'case_response') {
            return null;
        }

        return str_starts_with((string) $this->simulation_package, 'in_tray') ? 'in_tray' : 'critical_incident';
    }

    public function simulationThreeTrackLabel(): ?string
    {
        return match ($this->simulationThreeTrack()) {
            'in_tray' => 'In-Tray',
            'critical_incident' => 'Critical Incident',
            default => null,
        };
    }

    public function simulationThreePackageLabel(): ?string
    {
        return self::SIMULATION_THREE_PACKAGES[$this->simulation_package]['label'] ?? null;
    }

    public function simulationThreeAudience(): ?string
    {
        return self::SIMULATION_THREE_PACKAGES[$this->simulation_package]['audience'] ?? null;
    }
}
