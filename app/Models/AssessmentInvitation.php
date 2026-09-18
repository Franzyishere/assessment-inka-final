<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssessmentInvitation extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['valid_from' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_login_at' => 'datetime'];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(AssessmentParticipant::class, 'assessment_participant_id');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(AssessmentOtpChallenge::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AssessmentEmailDelivery::class);
    }

    public function latestInvitationDelivery(): HasOne
    {
        return $this->hasOne(AssessmentEmailDelivery::class)->ofMany(['id' => 'max'], fn ($query) => $query->where('kind', 'invitation'));
    }

    public function latestOtpDelivery(): HasOne
    {
        return $this->hasOne(AssessmentEmailDelivery::class)->ofMany(['id' => 'max'], fn ($query) => $query->where('kind', 'otp'));
    }

    public function isEligible(): bool
    {
        $this->loadMissing('participant.user', 'participant.program');
        $participant = $this->participant;
        $program = $participant?->program;

        return ! $this->revoked_at
            && $participant?->status === 'assigned'
            && $participant->user?->role === 'peserta_assessment'
            && strcasecmp($participant->user->email, $this->email) === 0
            && in_array($program?->status, ['draft', 'active'], true)
            && $program->starts_at
            && $program->starts_at->copy()->timezone(config('assessment_access.timezone'))->toDateString()
                === $this->valid_from->copy()->timezone(config('assessment_access.timezone'))->toDateString();
    }

    public function isAccessible(): bool
    {
        return $this->isEligible() && now()->gte($this->valid_from) && now()->lt($this->expires_at);
    }

    public function accessLabel(): string
    {
        if ($this->revoked_at) {
            return 'Dicabut';
        }
        if (! $this->isEligible()) {
            return 'Perlu diterbitkan ulang';
        }
        if (now()->gte($this->expires_at)) {
            return 'Kedaluwarsa';
        }

        return now()->lt($this->valid_from) ? 'Menunggu hari pelaksanaan' : 'Aktif';
    }
}
