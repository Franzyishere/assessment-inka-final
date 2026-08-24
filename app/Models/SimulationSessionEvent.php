<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationSessionEvent extends Model
{
    public const TYPES = ['fullscreen_exit', 'fullscreen_denied'];

    protected $fillable = ['simulation_session_id', 'event_type', 'metadata', 'ip_address', 'occurred_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class, 'simulation_session_id');
    }
}
