<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PsychologicalQuestion extends Model
{
    protected $fillable = ['psychological_test_version_id', 'number', 'is_required'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PsychologicalTestVersion::class, 'psychological_test_version_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PsychologicalQuestionOption::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PsychologicalAnswer::class);
    }
}
