<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PsychologicalTestSession extends Model
{
    protected $fillable = ['recruitment_batch_psychological_test_id', 'recruitment_participant_id', 'attempt_number', 'status', 'started_at', 'expires_at', 'last_activity_at', 'submitted_at', 'last_question_number', 'session_token'];

    protected $hidden = ['session_token'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'last_activity_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RecruitmentBatchPsychologicalTest::class, 'recruitment_batch_psychological_test_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentParticipant::class, 'recruitment_participant_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PsychologicalAnswer::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(PsychologicalResult::class);
    }
}
