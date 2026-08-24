<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationMaterialPage extends Model
{
    use HasFactory;

    protected $fillable = ['simulation_scenario_id', 'title', 'content', 'page_order', 'attachment_path', 'attachment_name', 'attachment_mime_type', 'attachment_size', 'is_required'];

    protected function casts(): array
    {
        return ['page_order' => 'integer', 'attachment_size' => 'integer', 'is_required' => 'boolean'];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(SimulationScenario::class, 'simulation_scenario_id');
    }
}
