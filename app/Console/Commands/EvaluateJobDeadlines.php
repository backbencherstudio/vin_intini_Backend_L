<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\IndustryJobPostStatus;
use App\Models\IndustryJobPost;
use Illuminate\Console\Command;

class EvaluateJobDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:evaluate-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire published job posts whose application deadline has passed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->startOfDay();

        $expiredCount = IndustryJobPost::where('status', IndustryJobPostStatus::PUBLISHED)
            ->whereNotNull('announcement_end_date')
            ->where('announcement_end_date', '<', $today)
            ->update([
                'status' => IndustryJobPostStatus::EXPIRED,
            ]);

        $this->info("Successfully evaluated job deadlines: {$expiredCount} jobs marked as expired.");

        return Command::SUCCESS;
    }
}
