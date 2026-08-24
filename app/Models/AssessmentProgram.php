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
}
