<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\TestWebhook::class,
        Commands\CleanupWebhooks::class,
        Commands\ListWebhooks::class,
        Commands\ToggleWebhook::class,
        Commands\RetryFailedWebhooks::class,
        Commands\MonitorWebhooks::class,
        Commands\RunBacktest::class,
        Commands\ListBacktests::class,
        Commands\CleanBacktests::class,
        Commands\GenerateBacktestReport::class,
        Commands\OptimizeStrategyParameters::class,
        Commands\AnalyzeStrategyCorrelation::class,
        Commands\VisualizeBacktestResults::class,
        Commands\CleanBacktestNotifications::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * These schedules are run in a default, single-server configuration.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Limpa webhooks inativos diariamente
        $schedule->command('webhooks:cleanup')
            ->daily()
            ->at('00:00')
            ->appendOutputTo(storage_path('logs/webhooks-cleanup.log'));

        // Tenta novamente webhooks que falharam a cada hora
        $schedule->command('webhooks:retry')
            ->hourly()
            ->appendOutputTo(storage_path('logs/webhooks-retry.log'));

        // Monitora webhooks a cada 15 minutos
        $schedule->command('webhooks:monitor')
            ->everyFifteenMinutes()
            ->appendOutputTo(storage_path('logs/webhooks-monitor.log'));

        // Clean old backtests every day at midnight
        $schedule->command('backtest:clean --days=30 --status=completed')
            ->daily()
            ->at('00:00');

        // Clean old notifications every day at 1 AM
        $schedule->command('backtest:clean-notifications --days=30')
            ->daily()
            ->at('01:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
