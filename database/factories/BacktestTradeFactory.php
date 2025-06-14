<?php

namespace Database\Factories;

use App\Models\Backtest;
use App\Models\BacktestTrade;
use Illuminate\Database\Eloquent\Factories\Factory;

class BacktestTradeFactory extends Factory
{
    protected $model = BacktestTrade::class;

    public function definition(): array
    {
        $entryPrice = $this->faker->randomFloat(2, 10000, 100000);
        $exitPrice = $this->faker->randomFloat(2, 10000, 100000);
        $quantity = $this->faker->randomFloat(8, 0.001, 1);
        $side = $this->faker->randomElement(['long', 'short']);
        $entryTime = $this->faker->dateTimeBetween('-1 month', '-1 day');
        $exitTime = $this->faker->dateTimeBetween($entryTime, 'now');

        if ($side === 'long') {
            $profitLoss = ($exitPrice - $entryPrice) * $quantity;
        } else {
            $profitLoss = ($entryPrice - $exitPrice) * $quantity;
        }

        $profitLossPercentage = ($profitLoss / ($entryPrice * $quantity)) * 100;

        return [
            'backtest_id' => Backtest::factory(),
            'symbol' => $this->faker->randomElement(['BTC/USDT', 'ETH/USDT', 'BNB/USDT']),
            'side' => $side,
            'entry_price' => $entryPrice,
            'exit_price' => $exitPrice,
            'quantity' => $quantity,
            'entry_time' => $entryTime,
            'exit_time' => $exitTime,
            'profit_loss' => $profitLoss,
            'profit_loss_percentage' => $profitLossPercentage,
            'stop_loss' => $side === 'long' ? $entryPrice * 0.98 : $entryPrice * 1.02,
            'take_profit' => $side === 'long' ? $entryPrice * 1.04 : $entryPrice * 0.96,
            'trailing_stop' => $this->faker->optional()->randomFloat(2, $entryPrice * 0.99, $entryPrice * 1.01),
            'highest_price' => max($entryPrice, $exitPrice),
            'lowest_price' => min($entryPrice, $exitPrice),
            'exit_reason' => $this->faker->randomElement(['take_profit', 'stop_loss', 'trailing_stop', 'signal']),
        ];
    }

    public function long(): self
    {
        return $this->state(function (array $attributes) {
            $entryPrice = $this->faker->randomFloat(2, 10000, 100000);
            $exitPrice = $entryPrice * $this->faker->randomFloat(2, 1.01, 1.1);
            $quantity = $this->faker->randomFloat(8, 0.001, 1);
            $profitLoss = ($exitPrice - $entryPrice) * $quantity;
            $profitLossPercentage = ($profitLoss / ($entryPrice * $quantity)) * 100;

            return [
                'side' => 'long',
                'entry_price' => $entryPrice,
                'exit_price' => $exitPrice,
                'quantity' => $quantity,
                'profit_loss' => $profitLoss,
                'profit_loss_percentage' => $profitLossPercentage,
                'stop_loss' => $entryPrice * 0.98,
                'take_profit' => $entryPrice * 1.04,
                'highest_price' => $exitPrice,
                'lowest_price' => $entryPrice,
            ];
        });
    }

    public function short(): self
    {
        return $this->state(function (array $attributes) {
            $entryPrice = $this->faker->randomFloat(2, 10000, 100000);
            $exitPrice = $entryPrice * $this->faker->randomFloat(2, 0.9, 0.99);
            $quantity = $this->faker->randomFloat(8, 0.001, 1);
            $profitLoss = ($entryPrice - $exitPrice) * $quantity;
            $profitLossPercentage = ($profitLoss / ($entryPrice * $quantity)) * 100;

            return [
                'side' => 'short',
                'entry_price' => $entryPrice,
                'exit_price' => $exitPrice,
                'quantity' => $quantity,
                'profit_loss' => $profitLoss,
                'profit_loss_percentage' => $profitLossPercentage,
                'stop_loss' => $entryPrice * 1.02,
                'take_profit' => $entryPrice * 0.96,
                'highest_price' => $entryPrice,
                'lowest_price' => $exitPrice,
            ];
        });
    }

    public function winning(): self
    {
        return $this->state(function (array $attributes) {
            $side = $this->faker->randomElement(['long', 'short']);
            $entryPrice = $this->faker->randomFloat(2, 10000, 100000);
            $exitPrice = $side === 'long'
                ? $entryPrice * $this->faker->randomFloat(2, 1.01, 1.1)
                : $entryPrice * $this->faker->randomFloat(2, 0.9, 0.99);
            $quantity = $this->faker->randomFloat(8, 0.001, 1);
            $profitLoss = $side === 'long'
                ? ($exitPrice - $entryPrice) * $quantity
                : ($entryPrice - $exitPrice) * $quantity;
            $profitLossPercentage = ($profitLoss / ($entryPrice * $quantity)) * 100;

            return [
                'side' => $side,
                'entry_price' => $entryPrice,
                'exit_price' => $exitPrice,
                'quantity' => $quantity,
                'profit_loss' => $profitLoss,
                'profit_loss_percentage' => $profitLossPercentage,
                'exit_reason' => $this->faker->randomElement(['take_profit', 'trailing_stop', 'signal']),
            ];
        });
    }

    public function losing(): self
    {
        return $this->state(function (array $attributes) {
            $side = $this->faker->randomElement(['long', 'short']);
            $entryPrice = $this->faker->randomFloat(2, 10000, 100000);
            $exitPrice = $side === 'long'
                ? $entryPrice * $this->faker->randomFloat(2, 0.9, 0.99)
                : $entryPrice * $this->faker->randomFloat(2, 1.01, 1.1);
            $quantity = $this->faker->randomFloat(8, 0.001, 1);
            $profitLoss = $side === 'long'
                ? ($exitPrice - $entryPrice) * $quantity
                : ($entryPrice - $exitPrice) * $quantity;
            $profitLossPercentage = ($profitLoss / ($entryPrice * $quantity)) * 100;

            return [
                'side' => $side,
                'entry_price' => $entryPrice,
                'exit_price' => $exitPrice,
                'quantity' => $quantity,
                'profit_loss' => $profitLoss,
                'profit_loss_percentage' => $profitLossPercentage,
                'exit_reason' => $this->faker->randomElement(['stop_loss', 'signal']),
            ];
        });
    }
}
