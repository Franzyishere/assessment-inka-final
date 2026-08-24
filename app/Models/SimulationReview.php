<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationReview extends Model
{
    use HasFactory;

    public const RECOMMENDED = 'recommended';

    public const CONSIDERED = 'considered';

    public const NOT_RECOMMENDED = 'not_recommended';

    protected $fillable = ['simulation_session_id', 'assessor_id', 'recommendation', 'assessment_notes', 'status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class, 'simulation_session_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }
}
