<?php

namespace App\Jobs;

use App\Mail\AssessmentAccessMail;
use App\Models\AssessmentEmailDelivery;
use App\Models\AssessmentInvitation;
use App\Services\AssessmentInvitationService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAssessmentInvitation implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public int $invitationId, public int $deliveryId, private string $token) {}

    public function handle(AssessmentInvitationService $service): void
    {
        $invitation = AssessmentInvitation::with('participant.program', 'participant.user')->find($this->invitationId);
        $delivery = AssessmentEmailDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->status !== 'queued') {
            return;
        }
        if (! $invitation || ! $invitation->isEligible() || now()->gte($invitation->expires_at)
            || ! hash_equals($invitation->token_hash, hash('sha256', $this->token))) {
            $delivery->update(['status' => 'cancelled']);

            return;
        }
        try {
            Mail::mailer($service->mailer())->to($invitation->email)->send(new AssessmentAccessMail(
                $invitation->participant->program->name,
                $invitation->valid_from->copy()->timezone(config('assessment_access.timezone'))->format('d-m-Y'),
                invitationUrl: route('assessment.invitation', $this->token),
            ));
            $delivery->update(['status' => 'accepted', 'sent_at' => now()]);
        } catch (\Throwable $exception) {
            $delivery->update(['status' => 'failed']);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        AssessmentEmailDelivery::whereKey($this->deliveryId)->where('status', 'queued')->update(['status' => 'failed']);
    }
}
