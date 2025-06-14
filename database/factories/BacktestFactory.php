<?php

namespace Database\Factories;

use App\Models\Backtest;
use App\Models\Exchange;
use App\Models\TradingStrategy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BacktestFactory extends Factory
{
    protected $model = Backtest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trading_strategy_id' => TradingStrategy::factory(),
            'exchange_id' => Exchange::factory(),
            'symbol' => $this->faker->randomElement(['BTC/USDT', 'ETH/USDT', 'BNB/USDT']),
            'timeframe' => $this->faker->randomElement(['1m', '5m', '15m', '1h', '4h', '1d']),
            'start_date' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            'end_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'initial_balance' => $this->faker->randomFloat(2, 100, 10000),
            'final_balance' => $this->faker->randomFloat(2, 100, 10000),
            'total_trades' => $this->faker->numberBetween(10, 1000),
            'winning_trades' => $this->faker->numberBetween(5, 500),
            'losing_trades' => $this->faker->numberBetween(5, 500),
            'win_rate' => $this->faker->randomFloat(2, 0, 100),
            'profit_factor' => $this->faker->randomFloat(2, 0, 5),
            'max_drawdown' => $this->faker->randomFloat(2, 0, 50),
            'sharpe_ratio' => $this->faker->randomFloat(2, -2, 4),
            'sortino_ratio' => $this->faker->randomFloat(2, -2, 4),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'error_message' => $this->faker->optional()->sentence(),
        ];
    }

    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'error_message' => null,
            ];
        });
    }

    public function failed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'error_message' => $this->faker->sentence(),
            ];
        });
    }

    public function running(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'running',
                'error_message' => null,
            ];
        });
    }

    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'error_message' => null,
            ];
        });
    }
}
