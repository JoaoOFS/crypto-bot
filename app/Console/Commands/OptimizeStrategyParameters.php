<?php

namespace App\Console\Commands;

use App\Models\TradingStrategy;
use App\Services\BacktestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeStrategyParameters extends Command
{
    protected $signature = 'strategy:optimize {strategy_id} {--symbol=BTC/USDT} {--timeframe=1h} {--start-date=2024-01-01} {--end-date=2024-03-01} {--initial-balance=1000} {--metric=sharpe_ratio}';
    protected $description = 'Optimize strategy parameters using grid search';

    private $backtestService;

    public function __construct(BacktestService $backtestService)
    {
        parent::__construct();
        $this->backtestService = $backtestService;
    }

    public function handle()
    {
        $strategyId = $this->argument('strategy_id');
        $strategy = TradingStrategy::findOrFail($strategyId);

        $this->info("Optimizing parameters for strategy: {$strategy->name}");
        $this->info("Using metric: {$this->option('metric')}");

        $parameters = $this->getParameterGrid($strategy->type);
        $results = [];

        $progressBar = $this->output->createProgressBar(count($parameters));
        $progressBar->start();

        foreach ($parameters as $params) {
            try {
                $backtest = $this->backtestService->runBacktest(
                    $strategyId,
                    $this->option('exchange_id'),
                    $this->option('symbol'),
                    $this->option('timeframe'),
                    $this->option('start-date'),
                    $this->option('end-date'),
                    $this->option('initial-balance'),
                    $params
                );

                $results[] = [
                    'parameters' => $params,
                    'metric' => $this->getMetricValue($backtest, $this->option('metric')),
                    'backtest' => $backtest
                ];

                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("Error testing parameters: " . $e->getMessage());
                continue;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Sort results by metric value
        usort($results, function ($a, $b) {
            return $b['metric'] <=> $a['metric'];
        });

        // Display top 5 results
        $this->info("\nTop 5 Parameter Sets:");
        $this->table(
            ['Parameters', 'Metric Value', 'Win Rate', 'Profit Factor', 'Sharpe Ratio'],
            array_map(function ($result) {
                return [
                    json_encode($result['parameters']),
                    number_format($result['metric'], 4),
                    number_format($result['backtest']->win_rate * 100, 2) . '%',
                    number_format($result['backtest']->profit_factor, 2),
                    number_format($result['backtest']->sharpe_ratio, 2)
                ];
            }, array_slice($results, 0, 5))
        );

        // Save best parameters to strategy
        if (!empty($results)) {
            $bestParams = $results[0]['parameters'];
            $strategy->parameters = $bestParams;
            $strategy->save();

            $this->info("\nBest parameters saved to strategy:");
            $this->table(
                ['Parameter', 'Value'],
                array_map(function ($key, $value) {
                    return [$key, $value];
                }, array_keys($bestParams), array_values($bestParams))
            );
        }

        return 0;
    }

    private function getParameterGrid(string $strategyType): array
    {
        $grids = [
            'rsi' => [
                'period' => range(7, 21, 2),
                'overbought' => range(65, 85, 5),
                'oversold' => range(15, 35, 5)
            ],
            'macd' => [
                'fast_period' => range(8, 16, 2),
                'slow_period' => range(20, 40, 5),
                'signal_period' => range(5, 15, 2)
            ],
            'bollinger_bands' => [
                'period' => range(10, 30, 5),
                'std_dev' => range(1.5, 3.0, 0.5)
            ]
        ];

        $grid = $grids[$strategyType] ?? [];
        return $this->generateParameterCombinations($grid);
    }

    private function generateParameterCombinations(array $grid): array
    {
        $keys = array_keys($grid);
        $values = array_values($grid);
        $combinations = [[]];

        foreach ($values as $i => $valueArray) {
            $tmp = [];
            foreach ($combinations as $combination) {
                foreach ($valueArray as $value) {
                    $tmp[] = $combination + [$keys[$i] => $value];
                }
            }
            $combinations = $tmp;
        }

        return $combinations;
    }

    private function getMetricValue($backtest, string $metric): float
    {
        return match ($metric) {
            'sharpe_ratio' => $backtest->sharpe_ratio,
            'sortino_ratio' => $backtest->sortino_ratio,
            'profit_factor' => $backtest->profit_factor,
            'win_rate' => $backtest->win_rate,
            'total_return' => ($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance,
            default => $backtest->sharpe_ratio
        };
    }
}
