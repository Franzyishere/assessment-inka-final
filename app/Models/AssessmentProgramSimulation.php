<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentProgramSimulation extends Model
{
    use HasFactory;

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
}
