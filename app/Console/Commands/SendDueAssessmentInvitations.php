<?php

namespace App\Console\Commands;

use App\Models\AssessmentProgram;
use Illuminate\Console\Command;

class SendDueAssessmentInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessment:send-due-invitations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis mengirimkan email undangan assessment 10 menit sebelum program aktif';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sentCount = AssessmentProgram::sendDueInvitations();

        if ($sentCount > 0) {
            $this->info("{$sentCount} undangan assessment masuk antrean pengiriman.");
        } else {
            $this->info('Tidak ada undangan assessment yang jatuh tempo untuk dikirimkan.');
        }

        return self::SUCCESS;
    }
}
