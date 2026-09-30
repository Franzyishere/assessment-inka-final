<?php

namespace App\Console\Commands;

use App\Models\AssessmentProgram;
use Illuminate\Console\Command;

class CompleteEndedAssessmentPrograms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessment:complete-ended-programs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis mengubah status program assessment aktif menjadi completed satu jam setelah waktu selesai (ends_at) terlewati';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $completedCount = AssessmentProgram::completeEndedPrograms();

        if ($completedCount > 0) {
            $this->info("Berhasil mengubah {$completedCount} program assessment aktif menjadi selesai (completed).");
        } else {
            $this->info('Tidak ada program assessment aktif yang jatuh tempo untuk diselesaikan.');
        }

        return self::SUCCESS;
    }
}

