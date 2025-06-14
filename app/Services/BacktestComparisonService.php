<?php

namespace App\Services;

use App\Models\Backtest;
use Illuminate\Support\Collection;

class BacktestComparisonService
{
    public function compare(array $backtestIds): array
    {
        $backtests = Backtest::whereIn('id', $backtestIds)
            ->with(['tradingStrategy', 'trades', 'equityCurve'])
            ->get();

        if ($backtests->isEmpty()) {
            throw new \InvalidArgumentException('No backtests found for comparison');
        }

        return [
            'metadata' => $this->compareMetadata($backtests),
            'performance' => $this->comparePerformance($backtests),
            'trades' => $this->compareTrades($backtests),
            'equity_curves' => $this->compareEquityCurves($backtests),
            'correlation' => $this->calculateCorrelation($backtests)
        ];
    }

    private function compareMetadata(Collection $backtests): array
    {
        return $backtests->map(function ($backtest) {
            return [
                'id' => $backtest->id,
                'strategy' => [
                    'id' => $backtest->tradingStrategy->id,
                    'name' => $backtest->tradingStrategy->name,
                    'parameters' => $backtest->parameters
                ],
                'symbol' => $backtest->symbol,
                'timeframe' => $backtest->timeframe,
                'start_date' => $backtest->start_date->format('Y-m-d H:i:s'),
                'end_date' => $backtest->end_date->format('Y-m-d H:i:s'),
                'initial_balance' => $backtest->initial_balance,
                'final_balance' => $backtest->final_balance
            ];
        })->toArray();
    }

    private function comparePerformance(Collection $backtests): array
    {
        return $backtests->map(function ($backtest) {
            return [
                'id' => $backtest->id,
                'total_return' => (($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance) * 100,
                'win_rate' => $backtest->win_rate * 100,
                'profit_factor' => $backtest->profit_factor,
                'sharpe_ratio' => $backtest->sharpe_ratio,
                'sortino_ratio' => $backtest->sortino_ratio,
                'max_drawdown' => $backtest->max_drawdown,
                'total_trades' => $backtest->total_trades,
                'winning_trades' => $backtest->winning_trades,
                'losing_trades' => $backtest->losing_trades,
                'average_trade' => $backtest->trades->avg('profit_loss'),
                'average_win' => $backtest->trades->where('profit_loss', '>', 0)->avg('profit_loss'),
                'average_loss' => $backtest->trades->where('profit_loss', '<', 0)->avg('profit_loss'),
                'largest_win' => $backtest->trades->max('profit_loss'),
                'largest_loss' => $backtest->trades->min('profit_loss'),
                'average_trade_duration' => $backtest->trades->avg(function ($trade) {
                    return $trade->getDuration();
                })
            ];
        })->toArray();
    }

    private function compareTrades(Collection $backtests): array
    {
        return $backtests->map(function ($backtest) {
            $trades = $backtest->trades;

            return [
                'id' => $backtest->id,
                'trade_distribution' => [
                    'long' => $trades->where('side', 'long')->count(),
                    'short' => $trades->where('side', 'short')->count()
                ],
                'profit_distribution' => [
                    'winning' => $trades->where('profit_loss', '>', 0)->count(),
                    'losing' => $trades->where('profit_loss', '<', 0)->count()
                ],
                'monthly_performance' => $this->calculateMonthlyPerformance($trades),
                'hourly_performance' => $this->calculateHourlyPerformance($trades),
                'duration_distribution' => $this->calculateDurationDistribution($trades)
            ];
        })->toArray();
    }

    private function compareEquityCurves(Collection $backtests): array
    {
        return $backtests->map(function ($backtest) {
            $equityCurve = $backtest->equityCurve;

            return [
                'id' => $backtest->id,
                'equity_points' => $equityCurve->map(function ($point) {
                    return [
                        'timestamp' => $point->timestamp->format('Y-m-d H:i:s'),
                        'equity' => $point->equity,
                        'drawdown' => $point->drawdown_percentage
                    ];
                })->toArray(),
                'drawdown_periods' => $this->calculateDrawdownPeriods($equityCurve)
            ];
        })->toArray();
    }

    private function calculateCorrelation(Collection $backtests): array
    {
        $correlations = [];
        $equityCurves = $backtests->mapWithKeys(function ($backtest) {
            return [$backtest->id => $backtest->equityCurve->pluck('equity')->toArray()];
        });

        foreach ($equityCurves as $id1 => $curve1) {
            foreach ($equityCurves as $id2 => $curve2) {
                if ($id1 >= $id2) continue;

                $correlation = $this->pearsonCorrelation($curve1, $curve2);

                $correlations[] = [
                    'backtest1' => $id1,
                    'backtest2' => $id2,
                    'correlation' => $correlation
                ];
            }
        }

        return $correlations;
    }

    private function calculateMonthlyPerformance(Collection $trades): array
    {
        $monthlyReturns = [];

        foreach ($trades as $trade) {
            $month = $trade->exit_time->format('Y-m');
            if (!isset($monthlyReturns[$month])) {
                $monthlyReturns[$month] = 0;
            }
            $monthlyReturns[$month] += $trade->profit_loss_percentage;
        }

        return $monthlyReturns;
    }

    private function calculateHourlyPerformance(Collection $trades): array
    {
        $hourlyReturns = array_fill(0, 24, 0);
        $hourlyCounts = array_fill(0, 24, 0);

        foreach ($trades as $trade) {
            $hour = (int) $trade->exit_time->format('G');
            $hourlyReturns[$hour] += $trade->profit_loss_percentage;
            $hourlyCounts[$hour]++;
        }

        return array_map(function ($return, $count) {
            return $count > 0 ? $return / $count : 0;
        }, $hourlyReturns, $hourlyCounts);
    }

    private function calculateDurationDistribution(Collection $trades): array
    {
        $durations = $trades->map(function ($trade) {
            return $trade->getDuration();
        })->toArray();

        $ranges = [
            '0-1h' => 0,
            '1-4h' => 0,
            '4-8h' => 0,
            '8-24h' => 0,
            '24h+' => 0
        ];

        foreach ($durations as $duration) {
            if ($duration <= 60) {
                $ranges['0-1h']++;
            } elseif ($duration <= 240) {
                $ranges['1-4h']++;
            } elseif ($duration <= 480) {
                $ranges['4-8h']++;
            } elseif ($duration <= 1440) {
                $ranges['8-24h']++;
            } else {
                $ranges['24h+']++;
            }
        }

        return $ranges;
    }

    private function calculateDrawdownPeriods(Collection $equityCurve): array
    {
        $periods = [];
        $inDrawdown = false;
        $startTime = null;
        $minEquity = null;

        foreach ($equityCurve as $point) {
            if (!$inDrawdown && $point->drawdown_percentage > 0) {
                $inDrawdown = true;
                $startTime = $point->timestamp;
                $minEquity = $point->equity;
            } elseif ($inDrawdown && $point->drawdown_percentage == 0) {
                $inDrawdown = false;
                $periods[] = [
                    'start' => $startTime->format('Y-m-d H:i:s'),
                    'end' => $point->timestamp->format('Y-m-d H:i:s'),
                    'duration' => $startTime->diffInMinutes($point->timestamp),
                    'max_drawdown' => (($equityCurve->max('equity') - $minEquity) / $equityCurve->max('equity')) * 100
                ];
            }
        }

        return $periods;
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
}
