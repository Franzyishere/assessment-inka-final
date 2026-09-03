<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PapiDimension extends Model
{
    protected $fillable = ['code', 'category', 'name', 'description', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scoringRules(): HasMany
    {
        return $this->hasMany(PapiScoringRule::class);
    }

    public function resultScores(): HasMany
    {
        return $this->hasMany(PsychologicalResultScore::class);
    }
}
