<?php

namespace App\Console\Commands;

use App\Models\AssessmentProgram;
use Illuminate\Console\Command;

class ActivateDueAssessmentPrograms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessment:activate-due-programs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis mengaktifkan program assessment berstatus draft yang waktu mulainya telah tiba';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $activatedCount = AssessmentProgram::activateDuePrograms();

        if ($activatedCount > 0) {
            $this->info("Berhasil mengaktifkan {$activatedCount} program assessment draft.");
        } else {
            $this->info('Tidak ada program assessment draft yang jatuh tempo untuk diaktifkan.');
        }

        return self::SUCCESS;
    }
}
