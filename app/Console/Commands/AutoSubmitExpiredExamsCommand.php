<?php

namespace App\Console\Commands;

use App\Modules\Exams\Services\ExamAttemptService;
use Illuminate\Console\Command;

class AutoSubmitExpiredExamsCommand extends Command
{
    protected $signature = 'exams:auto-submit-expired {--limit=200 : Maximum attempts per run}';

    protected $description = 'Auto-submit exam attempts whose timer expired (abandoned sessions included)';

    public function handle(ExamAttemptService $attempts): int
    {
        $limit = (int) $this->option('limit');
        $submitted = $attempts->autoSubmitExpiredAttempts($limit);

        $this->info("Auto-submitted {$submitted} expired exam attempt(s).");

        return self::SUCCESS;
    }
}
