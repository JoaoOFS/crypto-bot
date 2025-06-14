<?php

namespace App\Console\Commands;

use App\Models\BacktestNotification;
use Illuminate\Console\Command;

class CleanBacktestNotifications extends Command
{
    protected $signature = 'backtest:clean-notifications {--days=30} {--type=} {--force}';
    protected $description = 'Clean old backtest notifications';

    public function handle()
    {
        $days = $this->option('days');
        $type = $this->option('type');
        $force = $this->option('force');

        $query = BacktestNotification::query()
            ->where('created_at', '<', now()->subDays($days));

        if ($type) {
            $query->where('type', $type);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No notifications to clean.');
            return 0;
        }

        if (!$force && !$this->confirm("Are you sure you want to delete {$count} notifications?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} notifications.");

        return 0;
    }
}
