<?php

namespace App\Console\Commands;

use App\Models\Backtest;
use App\Models\TradingStrategy;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AnalyzeStrategyCorrelation extends Command
{
    protected $signature = 'strategy:correlation {--symbol=BTC/USDT} {--timeframe=1h} {--start-date=2024-01-01} {--end-date=2024-03-01} {--initial-balance=1000}';
    protected $description = 'Analyze correlation between different trading strategies';

    public function handle()
    {
        $strategies = TradingStrategy::all();

        if ($strategies->count() < 2) {
            $this->error('Need at least 2 strategies to analyze correlation.');
            return 1;
        }

        $this->info('Running backtests for all strategies...');
        $results = $this->runBacktestsForStrategies($strategies);

        $this->info('Calculating correlations...');
        $correlations = $this->calculateCorrelations($results);

        $this->displayResults($correlations, $results);

        return 0;
    }

    private function runBacktestsForStrategies(Collection $strategies): Collection
    {
        $results = collect();
        $progressBar = $this->output->createProgressBar($strategies->count());
        $progressBar->start();

        foreach ($strategies as $strategy) {
            try {
                $backtest = Backtest::create([
                    'trading_strategy_id' => $strategy->id,
                    'exchange_id' => 1, // Default exchange
                    'symbol' => $this->option('symbol'),
                    'timeframe' => $this->option('timeframe'),
                    'start_date' => $this->option('start-date'),
                    'end_date' => $this->option('end-date'),
                    'initial_balance' => $this->option('initial-balance'),
                    'status' => 'pending'
                ]);

                // Run backtest
                $backtest->run();

                $results->push([
                    'strategy' => $strategy,
                    'backtest' => $backtest,
                    'returns' => $this->calculateReturns($backtest)
                ]);

                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("Error running backtest for strategy {$strategy->name}: " . $e->getMessage());
                continue;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        return $results;
    }

    private function calculateReturns(Backtest $backtest): array
    {
        $equityCurve = $backtest->equityCurve()
            ->orderBy('timestamp')
            ->get();

        $returns = [];
        $previousEquity = $backtest->initial_balance;

        foreach ($equityCurve as $point) {
            $returns[] = ($point->equity - $previousEquity) / $previousEquity;
            $previousEquity = $point->equity;
        }

        return $returns;
    }

    private function calculateCorrelations(Collection $results): array
    {
        $correlations = [];
        $strategies = $results->pluck('strategy');

        foreach ($strategies as $i => $strategy1) {
            foreach ($strategies as $j => $strategy2) {
                if ($i >= $j) continue;

                $returns1 = $results[$i]['returns'];
                $returns2 = $results[$j]['returns'];

                $correlation = $this->pearsonCorrelation($returns1, $returns2);

                $correlations[] = [
                    'strategy1' => $strategy1->name,
                    'strategy2' => $strategy2->name,
                    'correlation' => $correlation
                ];
            }
        }

        return $correlations;
    }

    private function pearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y)) {
            throw new \InvalidArgumentException('Arrays must have the same length');
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));

        return $denominator == 0 ? 0 : $numerator / $denominator;
    }

    private function displayResults(array $correlations, Collection $results): void
    {
        // Display correlation matrix
        $this->info("\nCorrelation Matrix:");
        $this->table(
            ['Strategy 1', 'Strategy 2', 'Correlation'],
            array_map(function ($corr) {
                return [
                    $corr['strategy1'],
                    $corr['strategy2'],
                    number_format($corr['correlation'], 4)
                ];
            }, $correlations)
        );

        // Display strategy performance
        $this->info("\nStrategy Performance:");
        $this->table(
            ['Strategy', 'Win Rate', 'Profit Factor', 'Sharpe Ratio', 'Total Return'],
            $results->map(function ($result) {
                $backtest = $result['backtest'];
                return [
                    $result['strategy']->name,
                    number_format($backtest->win_rate * 100, 2) . '%',
                    number_format($backtest->profit_factor, 2),
                    number_format($backtest->sharpe_ratio, 2),
                    number_format(($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance * 100, 2) . '%'
                ];
            })
        );

        // Display diversification recommendations
        $this->displayDiversificationRecommendations($correlations, $results);
    }

    private function displayDiversificationRecommendations(array $correlations, Collection $results): void
    {
        $this->info("\nDiversification Recommendations:");

        // Find low correlation pairs
        $lowCorrelationPairs = array_filter($correlations, function ($corr) {
            return abs($corr['correlation']) < 0.3;
        });

        if (!empty($lowCorrelationPairs)) {
            $this->info("\nLow Correlation Strategy Pairs (Good for Diversification):");
            foreach ($lowCorrelationPairs as $pair) {
                $this->line("- {$pair['strategy1']} + {$pair['strategy2']} (Correlation: " . number_format($pair['correlation'], 4) . ")");
            }
        }

        // Find high correlation pairs
        $highCorrelationPairs = array_filter($correlations, function ($corr) {
            return abs($corr['correlation']) > 0.7;
        });

        if (!empty($highCorrelationPairs)) {
            $this->info("\nHigh Correlation Strategy Pairs (Consider Consolidating):");
            foreach ($highCorrelationPairs as $pair) {
                $this->line("- {$pair['strategy1']} + {$pair['strategy2']} (Correlation: " . number_format($pair['correlation'], 4) . ")");
            }
        }

        // Suggest optimal portfolio weights
        $this->suggestPortfolioWeights($results);
    }

    private function suggestPortfolioWeights(Collection $results): void
    {
        $this->info("\nSuggested Portfolio Weights (Based on Sharpe Ratio):");

        $totalSharpe = $results->sum(function ($result) {
            return $result['backtest']->sharpe_ratio;
        });

        if ($totalSharpe > 0) {
            $weights = $results->map(function ($result) use ($totalSharpe) {
                return [
                    'strategy' => $result['strategy']->name,
                    'weight' => number_format($result['backtest']->sharpe_ratio / $totalSharpe * 100, 2) . '%'
                ];
            });

            $this->table(
                ['Strategy', 'Suggested Weight'],
                $weights->toArray()
            );
        }
    }
}
