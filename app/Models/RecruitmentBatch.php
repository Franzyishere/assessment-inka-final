<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentBatch extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'scheduled', 'active', 'completed', 'cancelled'];

    protected $fillable = ['name', 'description', 'starts_at', 'ends_at', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(RecruitmentParticipant::class, 'recruitment_batch_participants')
            ->withPivot(['status', 'assigned_at'])
            ->withTimestamps();
    }

    public function psychologicalTests(): HasMany
    {
        return $this->hasMany(RecruitmentBatchPsychologicalTest::class);
    }
}
