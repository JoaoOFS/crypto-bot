<?php

namespace App\Console\Commands;

use App\Models\Backtest;
use Illuminate\Console\Command;

class CleanBacktests extends Command
{
    protected $signature = 'backtest:clean {--days=30 : Number of days to keep} {--status= : Status to clean}';
    protected $description = 'Clean old backtests';

    public function handle()
    {
        $days = $this->option('days');
        $status = $this->option('status');

        $query = Backtest::where('created_at', '<', now()->subDays($days));

        if ($status) {
            $query->where('status', $status);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No backtests to clean.');
            return 0;
        }

        if ($this->confirm("Are you sure you want to delete {$count} backtests?")) {
            $query->delete();
            $this->info("Deleted {$count} backtests.");
        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }
}
