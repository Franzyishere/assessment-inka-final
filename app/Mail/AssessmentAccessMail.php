<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AssessmentAccessMail extends Mailable
{
    public function __construct(public string $programName, public string $accessDate, public ?string $invitationUrl = null, public ?string $otp = null) {}

    public function build(): static
    {
        return $this->subject($this->otp ? 'Kode OTP INKA Assessment Portal' : 'Undangan INKA Assessment Portal')
            ->view('emails.assessment-access');
    }
}
