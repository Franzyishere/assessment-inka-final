<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychologicalResultScore extends Model
{
    protected $fillable = ['psychological_result_id', 'papi_dimension_id', 'score'];

    public function result(): BelongsTo
    {
        return $this->belongsTo(PsychologicalResult::class);
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(PapiDimension::class, 'papi_dimension_id');
    }
}
