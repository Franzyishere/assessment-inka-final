<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PsychologicalResult extends Model
{
    protected $fillable = ['psychological_test_session_id', 'status', 'total_role', 'total_need', 'scoring_version', 'invalid_reason', 'scored_at'];

    protected function casts(): array
    {
        return ['scored_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PsychologicalTestSession::class, 'psychological_test_session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PsychologicalResultScore::class);
    }
}
