<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationSubmission extends Model
{
    use HasFactory;

    protected $fillable = ['simulation_session_id', 'response_text', 'original_filename', 'storage_path', 'mime_type', 'file_size', 'file_checksum', 'revision', 'submitted_at'];

    protected function casts(): array
    {
        return ['file_size' => 'integer', 'revision' => 'integer', 'submitted_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class, 'simulation_session_id');
    }
}
