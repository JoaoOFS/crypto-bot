<?php

namespace Database\Seeders;

use App\Models\Backtest;
use App\Models\BacktestEquityCurve;
use App\Models\BacktestTrade;
use App\Models\Exchange;
use App\Models\TradingStrategy;
use App\Models\User;
use Illuminate\Database\Seeder;

class BacktestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create();
        $exchange = Exchange::factory()->create();
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Strategy',
            'type' => 'trend_following',
            'parameters' => [
                'rsi_period' => 14,
                'rsi_overbought' => 70,
                'rsi_oversold' => 30,
            ],
        ]);

        // Create a completed backtest
        $completedBacktest = Backtest::factory()->completed()->create([
            'user_id' => $user->id,
            'trading_strategy_id' => $strategy->id,
            'exchange_id' => $exchange->id,
            'symbol' => 'BTC/USDT',
            'timeframe' => '1h',
            'start_date' => now()->subMonths(3),
            'end_date' => now(),
            'initial_balance' => 1000,
            'final_balance' => 1500,
            'total_trades' => 100,
            'winning_trades' => 60,
            'losing_trades' => 40,
            'win_rate' => 60,
            'profit_factor' => 1.5,
            'max_drawdown' => 15,
            'sharpe_ratio' => 1.2,
            'sortino_ratio' => 1.5,
        ]);

        // Create trades for the completed backtest
        BacktestTrade::factory()
            ->count(60)
            ->winning()
            ->create(['backtest_id' => $completedBacktest->id]);

        BacktestTrade::factory()
            ->count(40)
            ->losing()
            ->create(['backtest_id' => $completedBacktest->id]);

        // Create equity curve for the completed backtest
        $equity = 1000;
        $maxEquity = 1000;
        $timestamps = collect(range(0, 90))->map(function ($day) {
            return now()->subDays(90 - $day);
        });

        foreach ($timestamps as $timestamp) {
            $equity += $this->faker->randomFloat(2, -50, 100);
            $maxEquity = max($maxEquity, $equity);
            $drawdown = $maxEquity - $equity;
            $drawdownPercentage = ($drawdown / $maxEquity) * 100;

            BacktestEquityCurve::factory()->create([
                'backtest_id' => $completedBacktest->id,
                'timestamp' => $timestamp,
                'equity' => $equity,
                'drawdown' => $drawdown,
                'drawdown_percentage' => $drawdownPercentage,
            ]);
        }

        // Create a failed backtest
        Backtest::factory()->failed()->create([
            'user_id' => $user->id,
            'trading_strategy_id' => $strategy->id,
            'exchange_id' => $exchange->id,
            'symbol' => 'ETH/USDT',
            'timeframe' => '4h',
            'start_date' => now()->subMonths(2),
            'end_date' => now(),
            'initial_balance' => 1000,
            'error_message' => 'Failed to fetch historical data',
        ]);

        // Create a running backtest
        Backtest::factory()->running()->create([
            'user_id' => $user->id,
            'trading_strategy_id' => $strategy->id,
            'exchange_id' => $exchange->id,
            'symbol' => 'BNB/USDT',
            'timeframe' => '1d',
            'start_date' => now()->subMonth(),
            'end_date' => now(),
            'initial_balance' => 1000,
        ]);

        // Create a pending backtest
        Backtest::factory()->pending()->create([
            'user_id' => $user->id,
            'trading_strategy_id' => $strategy->id,
            'exchange_id' => $exchange->id,
            'symbol' => 'BTC/USDT',
            'timeframe' => '15m',
            'start_date' => now()->subWeek(),
            'end_date' => now(),
            'initial_balance' => 1000,
        ]);
    }
}
