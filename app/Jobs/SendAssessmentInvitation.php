<?php

namespace App\Jobs;

use App\Models\AssessmentEmailDelivery;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Compatibility tombstone: safely drain jobs queued before private-code login.
 * Keep until all old queues and failed jobs have been retired.
 */
class SendAssessmentInvitation implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public int $invitationId, public int $deliveryId, private string $token) {}

    public function handle(): void
    {
        AssessmentEmailDelivery::whereKey($this->deliveryId)->where('status', 'queued')
            ->update(['status' => 'cancelled']);
    }
}
