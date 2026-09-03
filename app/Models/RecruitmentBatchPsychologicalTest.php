<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentBatchPsychologicalTest extends Model
{
    protected $fillable = ['recruitment_batch_id', 'psychological_test_version_id', 'available_from', 'available_until', 'duration_minutes', 'max_attempts', 'is_active'];

    protected function casts(): array
    {
        return ['available_from' => 'datetime', 'available_until' => 'datetime', 'is_active' => 'boolean'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(RecruitmentBatch::class, 'recruitment_batch_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PsychologicalTestVersion::class, 'psychological_test_version_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PsychologicalTestSession::class);
    }
}
