<?php

namespace App\Jobs;

use App\Models\Backtest;
use App\Services\BacktestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunBacktestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backtest;

    public function __construct(Backtest $backtest)
    {
        $this->backtest = $backtest;
    }

    public function handle(BacktestService $backtestService): void
    {
        try {
            Log::info("Starting backtest #{$this->backtest->id}");
            $backtestService->runBacktest($this->backtest);
            Log::info("Backtest #{$this->backtest->id} completed successfully");
        } catch (\Exception $e) {
            Log::error("Backtest #{$this->backtest->id} failed: {$e->getMessage()}");
            $this->backtest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Backtest #{$this->backtest->id} failed: {$exception->getMessage()}");
        $this->backtest->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
