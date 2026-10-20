<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAccessCode extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['secret', 'secret_hash', 'version'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(AssessmentParticipant::class, 'assessment_participant_id');
    }
}
