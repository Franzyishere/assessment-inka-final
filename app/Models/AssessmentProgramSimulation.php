<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentProgramSimulation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['assessment_program_id', 'simulation_scenario_id', 'opens_at', 'closes_at', 'status'];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AssessmentProgram::class, 'assessment_program_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(SimulationScenario::class, 'simulation_scenario_id');
    }

    public function assessorAssignments(): HasMany
    {
        return $this->hasMany(AssessorAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SimulationSession::class);
    }

    public function sequence(): int
    {
        return (int) ($this->scenario?->type?->sequence ?? 1);
    }

    public function isPresentation(): bool
    {
        return $this->sequence() === 4 || $this->scenario?->type?->delivery_mode === 'file_upload';
    }

    public function isAlwaysOpen(): bool
    {
        return $this->isPresentation();
    }

    public function isAvailableForParticipant(): bool
    {
        if ($this->program?->status !== 'active') {
            return false;
        }

        if ($this->scenario && ! in_array($this->scenario->status, ['active', 'published'], true)) {
            return false;
        }

        if ($this->scenario?->type && ! $this->scenario->type->is_active) {
            return false;
        }

        if ($this->isAlwaysOpen()) {
            return $this->status !== self::STATUS_COMPLETED;
        }

        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function statusLabel(): string
    {
        if ($this->isAlwaysOpen()) {
            return $this->status === self::STATUS_COMPLETED ? 'Ditutup' : 'Selalu Terbuka';
        }

        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'Sedang Dibuka',
            self::STATUS_COMPLETED => 'Ditutup',
            default => 'Belum Dimulai',
        };
    }

    public function statusBadgeClass(): string
    {
        if ($this->isAlwaysOpen()) {
            return $this->status === self::STATUS_COMPLETED
                ? 'bg-gray-100 text-gray-700 border border-gray-200'
                : 'bg-emerald-100 text-emerald-800 border border-emerald-300';
        }

        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'bg-emerald-100 text-emerald-800 border border-emerald-300 font-semibold',
            self::STATUS_COMPLETED => 'bg-gray-100 text-gray-700 border border-gray-200',
            default => 'bg-amber-50 text-amber-800 border border-amber-200',
        };
    }
}
