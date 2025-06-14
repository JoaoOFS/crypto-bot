<?php

namespace App\Services;

use App\Models\Backtest;
use App\Models\BacktestNotification;
use Illuminate\Support\Facades\Log;

class BacktestNotificationService
{
    public function notifyBacktestCompleted(Backtest $backtest): void
    {
        $this->createNotification(
            $backtest,
            'success',
            'Backtest Completed',
            "Backtest #{$backtest->id} has completed successfully.",
            [
                'win_rate' => $backtest->win_rate,
                'profit_factor' => $backtest->profit_factor,
                'sharpe_ratio' => $backtest->sharpe_ratio,
                'total_trades' => $backtest->total_trades
            ]
        );

        $this->notifySignificantResults($backtest);
    }

    public function notifyBacktestFailed(Backtest $backtest, string $error): void
    {
        $this->createNotification(
            $backtest,
            'error',
            'Backtest Failed',
            "Backtest #{$backtest->id} has failed: {$error}"
        );
    }

    public function notifyBacktestStarted(Backtest $backtest): void
    {
        $this->createNotification(
            $backtest,
            'info',
            'Backtest Started',
            "Backtest #{$backtest->id} has started running."
        );
    }

    public function notifyParameterOptimization(Backtest $backtest, array $bestParameters): void
    {
        $this->createNotification(
            $backtest,
            'info',
            'Parameter Optimization Complete',
            "Parameter optimization for Backtest #{$backtest->id} is complete.",
            ['best_parameters' => $bestParameters]
        );
    }

    private function notifySignificantResults(Backtest $backtest): void
    {
        // Notify if win rate is exceptional
        if ($backtest->win_rate >= 0.7) {
            $this->createNotification(
                $backtest,
                'success',
                'Exceptional Win Rate',
                "Backtest #{$backtest->id} achieved an exceptional win rate of " .
                number_format($backtest->win_rate * 100, 2) . "%"
            );
        }

        // Notify if drawdown is concerning
        if ($backtest->max_drawdown >= 0.2) {
            $this->createNotification(
                $backtest,
                'warning',
                'High Drawdown Warning',
                "Backtest #{$backtest->id} experienced a significant drawdown of " .
                number_format($backtest->max_drawdown * 100, 2) . "%"
            );
        }

        // Notify if Sharpe ratio is exceptional
        if ($backtest->sharpe_ratio >= 2) {
            $this->createNotification(
                $backtest,
                'success',
                'Excellent Risk-Adjusted Returns',
                "Backtest #{$backtest->id} achieved an excellent Sharpe ratio of " .
                number_format($backtest->sharpe_ratio, 2)
            );
        }
    }

    private function createNotification(
        Backtest $backtest,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        try {
            BacktestNotification::create([
                'backtest_id' => $backtest->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data
            ]);

            // Log notification for debugging
            Log::info("Backtest notification created", [
                'backtest_id' => $backtest->id,
                'type' => $type,
                'title' => $title
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create backtest notification", [
                'backtest_id' => $backtest->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
