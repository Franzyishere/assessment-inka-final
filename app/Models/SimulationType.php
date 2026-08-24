<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationType extends Model
{
    use HasFactory;

    public const PROBLEM_ANALYSIS = 'problem_analysis';

    public const LGD = 'leaderless_group_discussion';

    public const CRITICAL_INCIDENT = 'critical_incident';

    public const PRESENTATION = 'presentation';

    protected $fillable = ['code', 'name', 'delivery_mode', 'sequence', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sequence' => 'integer'];
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(SimulationScenario::class);
    }
}
