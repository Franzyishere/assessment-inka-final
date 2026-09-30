<?php

namespace App\Jobs;

use App\Mail\AssessmentAccessMail;
use App\Models\AssessmentEmailDelivery;
use App\Models\AssessmentInvitation;
use App\Services\AssessmentInvitationService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAssessmentInvitation implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 180];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('assessment-email-'.$this->deliveryId))
            ->releaseAfter(60)->expireAfter(90)];
    }

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
            Log::warning('Invitation email delivery attempt failed.', [
                'delivery_id' => $this->deliveryId, 'error_type' => $exception::class,
            ]);
            // Do not persist transport details or a previous exception containing secrets.
            throw new \RuntimeException('Pengiriman undangan gagal; periksa layanan email.');
        }
    }

    public function failed(?\Throwable $exception): void
    {
        AssessmentEmailDelivery::whereKey($this->deliveryId)->where('status', 'queued')->update(['status' => 'failed']);
    }
}
