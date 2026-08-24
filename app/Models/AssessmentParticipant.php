<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentParticipant extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'grade_1_to_2' => 'Kenaikan Golongan I ke II',
        'grade_2_to_3' => 'Kenaikan Golongan II ke III',
        'grade_3_to_4' => 'Kenaikan Golongan III ke IV',
        'promotion_spv' => 'Promosi SPV',
        'promotion_specialist_young' => 'Promosi Spesialis Muda',
        'promotion_specialist_middle' => 'Promosi Spesialis Madya',
        'promotion_specialist_pratama' => 'Promosi Spesialis Pratama',
        'promotion_m' => 'Promosi M',
        'promotion_sm' => 'Promosi SM',
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
        return $this->assessment_category === self::MADYA_CATEGORY && ! $this->simulation_three_choice;
    }

    public function simulationThreePackageKey(): ?string
    {
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
            return in_array($package, ['ci_3', 'in_tray_3'], true);
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
