<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationSession extends Model
{
    use HasFactory;

    protected $fillable = ['assessment_program_simulation_id', 'assessment_participant_id', 'status', 'started_at', 'expires_at', 'submitted_at', 'session_token', 'device_identifier', 'last_ip_address', 'last_user_agent'];

    protected $hidden = ['session_token'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function programSimulation(): BelongsTo
    {
        return $this->belongsTo(AssessmentProgramSimulation::class, 'assessment_program_simulation_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(AssessmentParticipant::class, 'assessment_participant_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SimulationSubmission::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SimulationReview::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SimulationSessionEvent::class);
    }
}
