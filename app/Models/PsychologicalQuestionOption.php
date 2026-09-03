<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PsychologicalQuestionOption extends Model
{
    protected $fillable = ['psychological_question_id', 'code', 'statement', 'display_order'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(PsychologicalQuestion::class);
    }

    public function scoringRule(): HasOne
    {
        return $this->hasOne(PapiScoringRule::class);
    }
}
