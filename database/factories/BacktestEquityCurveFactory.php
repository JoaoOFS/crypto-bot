<?php

namespace Database\Factories;

use App\Models\Backtest;
use App\Models\BacktestEquityCurve;
use Illuminate\Database\Eloquent\Factories\Factory;

class BacktestEquityCurveFactory extends Factory
{
    protected $model = BacktestEquityCurve::class;

    public function definition(): array
    {
        $equity = $this->faker->randomFloat(2, 1000, 10000);
        $maxEquity = $equity * 1.1;
        $drawdown = $maxEquity - $equity;
        $drawdownPercentage = ($drawdown / $maxEquity) * 100;

        return [
            'backtest_id' => Backtest::factory(),
            'timestamp' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'equity' => $equity,
            'drawdown' => $drawdown,
            'drawdown_percentage' => $drawdownPercentage,
        ];
    }

    public function profitable(): self
    {
        return $this->state(function (array $attributes) {
            $equity = $this->faker->randomFloat(2, 1100, 10000);
            $maxEquity = $equity * 1.05;
            $drawdown = $maxEquity - $equity;
            $drawdownPercentage = ($drawdown / $maxEquity) * 100;

            return [
                'equity' => $equity,
                'drawdown' => $drawdown,
                'drawdown_percentage' => $drawdownPercentage,
            ];
        });
    }

    public function losing(): self
    {
        return $this->state(function (array $attributes) {
            $equity = $this->faker->randomFloat(2, 900, 1000);
            $maxEquity = 1000;
            $drawdown = $maxEquity - $equity;
            $drawdownPercentage = ($drawdown / $maxEquity) * 100;

            return [
                'equity' => $equity,
                'drawdown' => $drawdown,
                'drawdown_percentage' => $drawdownPercentage,
            ];
        });
    }

    public function highDrawdown(): self
    {
        return $this->state(function (array $attributes) {
            $equity = $this->faker->randomFloat(2, 500, 1000);
            $maxEquity = 1000;
            $drawdown = $maxEquity - $equity;
            $drawdownPercentage = ($drawdown / $maxEquity) * 100;

            return [
                'equity' => $equity,
                'drawdown' => $drawdown,
                'drawdown_percentage' => $drawdownPercentage,
            ];
        });
    }
}
