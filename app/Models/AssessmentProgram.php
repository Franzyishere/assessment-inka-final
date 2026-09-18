<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentProgram extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'starts_at', 'ends_at', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function simulations(): HasMany
    {
        return $this->hasMany(AssessmentProgramSimulation::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AssessmentParticipant::class);
    }

    public static function activateDuePrograms(): int
    {
        return static::where('status', 'draft')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->update(['status' => 'active']);
    }

    public function usesSharedSimulationThree(): bool
    {
        // The assigned scenario identifies the ruleset; no historical row is rewritten.
        $this->loadMissing('simulations.scenario.type');

        return $this->simulations->contains(fn ($simulation) => $simulation->scenario->usesSharedSimulationThreeMaterial());
    }
}
