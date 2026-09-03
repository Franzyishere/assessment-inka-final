<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PapiScoringRule extends Model
{
    protected $fillable = ['psychological_question_option_id', 'papi_dimension_id', 'weight'];

    public function option(): BelongsTo
    {
        return $this->belongsTo(PsychologicalQuestionOption::class, 'psychological_question_option_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(PapiDimension::class, 'papi_dimension_id');
    }
}
