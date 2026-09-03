<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychologicalAnswer extends Model
{
    protected $fillable = ['psychological_test_session_id', 'psychological_question_id', 'psychological_question_option_id', 'answered_at'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PsychologicalTestSession::class, 'psychological_test_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(PsychologicalQuestion::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(PsychologicalQuestionOption::class, 'psychological_question_option_id');
    }
}
