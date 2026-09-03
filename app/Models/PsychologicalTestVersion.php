<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PsychologicalTestVersion extends Model
{
    public const STATUSES = ['draft', 'validated', 'published', 'archived'];

    protected $fillable = ['psychological_test_id', 'version', 'item_count', 'duration_minutes', 'expected_role_total', 'expected_need_total', 'status', 'validated_at', 'validated_by', 'published_at', 'published_by'];

    protected function casts(): array
    {
        return ['validated_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(PsychologicalTest::class, 'psychological_test_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PsychologicalQuestion::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RecruitmentBatchPsychologicalTest::class);
    }
}
