<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentProgram extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'starts_at', 'ends_at', 'status', 'auto_send_invitations', 'created_by'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'auto_send_invitations' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function simulations(): HasMany
    {
        return $this->hasMany(AssessmentProgramSimulation::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AssessmentParticipant::class);
    }

    public static function syncLifecycle(): void
    {
        static::activateDuePrograms();
        static::completeEndedPrograms();
    }

    public static function activateDuePrograms(): int
    {
        return static::where('status', 'draft')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->update(['status' => 'active']);
    }

    public static function completeEndedPrograms(): int
    {
        return static::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now()->subHour())
            ->update(['status' => 'completed']);
    }

    public static function sendDueInvitations(): int
    {
        $programs = static::query()
            ->where('auto_send_invitations', true)
            ->whereIn('status', ['draft', 'active'])
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now()->addMinutes(10))
            ->whereHas('participants', fn ($q) => $q->where('status', 'assigned'))
            ->with(['participants' => fn ($q) => $q->where('status', 'assigned')])
            ->get();

        $service = app(\App\Services\AssessmentInvitationService::class);
        $totalSent = 0;

        foreach ($programs as $program) {
            $start = $program->starts_at->copy()->timezone(config('assessment_access.timezone'))->startOfDay();
            $closeHour = (int) config('assessment_access.close_hour', 17);
            $closeMinute = (int) config('assessment_access.close_minute', 0);
            $end = $start->copy()->setTime($closeHour, $closeMinute, 0);
            if (now()->gte($end)) {
                continue;
            }

            foreach ($program->participants as $participant) {
                try {
                    if ($service->issue($participant, (int) $program->created_by, automatic: true)) {
                        $totalSent++;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Automatic invitation could not be queued.', ['participant_id' => $participant->id, 'error_type' => $e::class]);
                }
            }
        }

        return $totalSent;
    }

    public function usesSharedSimulationThree(): bool
    {
        // The assigned scenario identifies the ruleset; no historical row is rewritten.
        $this->loadMissing('simulations.scenario.type');

        return $this->simulations->contains(fn ($simulation) => $simulation->scenario->usesSharedSimulationThreeMaterial());
    }
}
