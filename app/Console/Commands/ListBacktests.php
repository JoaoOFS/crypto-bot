<?php

namespace App\Console\Commands;

use App\Models\Backtest;
use Illuminate\Console\Command;

class ListBacktests extends Command
{
    protected $signature = 'backtest:list {--status= : Filter by status} {--strategy= : Filter by strategy ID}';
    protected $description = 'List all backtests';

    public function handle()
    {
        $query = Backtest::with(['tradingStrategy', 'exchange']);

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        if ($strategy = $this->option('strategy')) {
            $query->where('trading_strategy_id', $strategy);
        }

        $backtests = $query->latest()->get();

        if ($backtests->isEmpty()) {
            $this->info('No backtests found.');
            return 0;
        }

        $headers = ['ID', 'Strategy', 'Symbol', 'Timeframe', 'Period', 'Status', 'Initial Balance', 'Final Balance', 'Win Rate'];
        $rows = $backtests->map(function ($backtest) {
            return [
                $backtest->id,
                $backtest->tradingStrategy->name,
                $backtest->symbol,
                $backtest->timeframe,
                "{$backtest->start_date->format('Y-m-d')} to {$backtest->end_date->format('Y-m-d')}",
                $backtest->status,
                $backtest->initial_balance,
                $backtest->final_balance ?? 'N/A',
                $backtest->win_rate ? "{$backtest->win_rate}%" : 'N/A',
            ];
        });

        $this->table($headers, $rows);

        return 0;
    }
}
