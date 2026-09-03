<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentParticipant extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'participant_number'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(RecruitmentBatch::class, 'recruitment_batch_participants')
            ->withPivot(['status', 'assigned_at'])
            ->withTimestamps();
    }

    public function psychologicalTestSessions(): HasMany
    {
        return $this->hasMany(PsychologicalTestSession::class);
    }
}
