<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentParticipant extends Model
{
    public function invitation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssessmentInvitation::class);
    }

    use HasFactory;

    public const CATEGORIES = [
        'grade_1_to_2' => 'Kenaikan Golongan I ke II',
        'grade_2_to_3' => 'Kenaikan Golongan II ke III',
        'grade_3_to_4' => 'Kenaikan Golongan III ke IV',
        'promotion_spv' => 'Level SPV',
        'promotion_specialist_young' => 'Level Spesialis Muda',
        'promotion_specialist_middle' => 'Level Spesialis Madya',
        'promotion_specialist_pratama' => 'Level Spesialis Pratama',
        'promotion_m' => 'Level M',
        'promotion_sm' => 'Level SM',
    ];

    public const IN_TRAY_CATEGORIES = ['promotion_m', 'promotion_sm'];

    public const MADYA_CATEGORY = 'promotion_specialist_middle';

    protected $fillable = ['assessment_program_id', 'user_id', 'assessment_category', 'simulation_three_choice', 'simulation_three_chosen_at', 'status', 'assigned_at'];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->assessment_category] ?? 'Belum ditentukan';
    }

    public function simulationThreeTrack(): string
    {
        return str_starts_with((string) $this->simulationThreePackageKey(), 'in_tray') ? 'In-Tray' : 'Critical Incident';
    }

    public function simulationThreeTrackKey(): string
    {
        return str_starts_with((string) $this->simulationThreePackageKey(), 'in_tray') ? 'in_tray' : 'critical_incident';
    }

    public function requiresSimulationThreeChoice(): bool
    {
        return ($this->usesSharedSimulationThree() || $this->assessment_category === self::MADYA_CATEGORY)
            && ! $this->simulationThreePackageKey();
    }

    public function usesSharedSimulationThree(): bool
    {
        return $this->program->usesSharedSimulationThree();
    }

    public function suggestedSimulationThreePackage(): ?string
    {
        return in_array($this->assessment_category, self::IN_TRAY_CATEGORIES, true) ? 'in_tray' : null;
    }

    public function pendingSimulationThreePackage(): string
    {
        if (! $this->usesSharedSimulationThree()) {
            return 'ci_3';
        }

        $available = $this->program->simulations->pluck('scenario.simulation_package')->filter();

        return $available->contains($this->suggestedSimulationThreePackage())
            ? $this->suggestedSimulationThreePackage()
            : ($available->first() ?? 'ci_short');
    }

    public function simulationThreePackageKey(): ?string
    {
        if ($this->usesSharedSimulationThree()) {
            return (array_key_exists((string) $this->simulation_three_choice, SimulationScenario::SIMULATION_THREE_PACKAGES)
                || $this->simulation_three_choice === 'ci')
                ? $this->simulation_three_choice : null;
        }

        if ($this->simulation_three_choice) {
            return $this->simulation_three_choice;
        }

        if ($this->assessment_category === self::MADYA_CATEGORY) {
            return $this->simulation_three_choice;
        }

        return match ($this->assessment_category) {
            'grade_1_to_2', 'promotion_specialist_pratama', 'promotion_spv' => 'ci_1',
            'grade_2_to_3', 'promotion_specialist_young' => 'ci_2',
            'grade_3_to_4' => 'ci_3',
            'promotion_m' => 'in_tray_1',
            'promotion_sm' => 'in_tray_2',
            default => null,
        };
    }

    public function isEligibleForSimulationThreePackage(?string $package): bool
    {
        if (! $package) {
            return false;
        }

        if ($this->requiresSimulationThreeChoice()) {
            return $this->usesSharedSimulationThree()
                ? (array_key_exists($package, SimulationScenario::SIMULATION_THREE_PACKAGES) || $package === 'ci')
                : in_array($package, ['ci_3', 'in_tray_3'], true);
        }

        return $this->simulationThreePackageKey() === $package;
    }

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'simulation_three_chosen_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AssessmentProgram::class, 'assessment_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SimulationSession::class);
    }
}
