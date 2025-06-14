<?php

namespace App\Console\Commands;

use App\Models\Backtest;
use App\Services\BacktestService;
use Illuminate\Console\Command;

class RunBacktest extends Command
{
    protected $signature = 'backtest:run {id : The ID of the backtest to run}';
    protected $description = 'Run a backtest';

    protected $backtestService;

    public function __construct(BacktestService $backtestService)
    {
        parent::__construct();
        $this->backtestService = $backtestService;
    }

    public function handle()
    {
        $id = $this->argument('id');
        $backtest = Backtest::findOrFail($id);

        $this->info("Starting backtest #{$backtest->id}...");
        $this->info("Strategy: {$backtest->tradingStrategy->name}");
        $this->info("Symbol: {$backtest->symbol}");
        $this->info("Timeframe: {$backtest->timeframe}");
        $this->info("Period: {$backtest->start_date} to {$backtest->end_date}");
        $this->info("Initial Balance: {$backtest->initial_balance}");

        try {
            $this->backtestService->runBacktest($backtest);
            $backtest->refresh();

            $this->info("\nBacktest completed!");
            $this->info("Status: {$backtest->status}");
            $this->info("Final Balance: {$backtest->final_balance}");
            $this->info("Total Trades: {$backtest->total_trades}");
            $this->info("Winning Trades: {$backtest->winning_trades}");
            $this->info("Losing Trades: {$backtest->losing_trades}");
            $this->info("Win Rate: {$backtest->win_rate}%");
            $this->info("Profit Factor: {$backtest->profit_factor}");
            $this->info("Max Drawdown: {$backtest->max_drawdown}%");
            $this->info("Sharpe Ratio: {$backtest->sharpe_ratio}");
            $this->info("Sortino Ratio: {$backtest->sortino_ratio}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Backtest failed: {$e->getMessage()}");
            return 1;
        }
    }
}
