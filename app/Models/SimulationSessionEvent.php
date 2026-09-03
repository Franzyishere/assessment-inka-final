<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationSessionEvent extends Model
{
    public const TYPES = ['fullscreen_exit', 'fullscreen_denied', 'tab_hidden'];

    public const LABELS = [
        'fullscreen_exit' => 'Keluar fullscreen',
        'fullscreen_denied' => 'Fullscreen ditolak browser',
        'tab_hidden' => 'Berpindah tab',
    ];

    public function typeLabel(): string
    {
        return self::LABELS[$this->event_type] ?? ucwords(str_replace('_', ' ', $this->event_type));
    }

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
