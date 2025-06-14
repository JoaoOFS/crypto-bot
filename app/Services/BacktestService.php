<?php

namespace App\Services;

use App\Models\Backtest;
use App\Models\BacktestTrade;
use App\Models\BacktestEquityCurve;
use App\Models\TradingStrategy;
use App\Services\TechnicalAnalysisService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Exchange;
use App\Services\BacktestNotificationService;

class BacktestService
{
    protected $technicalAnalysisService;
    private $notificationService;

    public function __construct(TechnicalAnalysisService $technicalAnalysisService, BacktestNotificationService $notificationService)
    {
        $this->technicalAnalysisService = $technicalAnalysisService;
        $this->notificationService = $notificationService;
    }

    public function runBacktest(
        int $strategyId,
        int $exchangeId,
        string $symbol,
        string $timeframe,
        string $startDate,
        string $endDate,
        float $initialBalance,
        array $parameters = []
    ): Backtest {
        $strategy = TradingStrategy::findOrFail($strategyId);
        $exchange = Exchange::findOrFail($exchangeId);

        $backtest = Backtest::create([
            'trading_strategy_id' => $strategyId,
            'exchange_id' => $exchangeId,
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'initial_balance' => $initialBalance,
            'status' => 'running',
            'parameters' => $parameters ?: $strategy->parameters
        ]);

        try {
            $this->notificationService->notifyBacktestStarted($backtest);

            // Fetch historical data
            $historicalData = $this->fetchHistoricalData($exchange, $symbol, $timeframe, $startDate, $endDate);

            // Initialize variables
            $balance = $initialBalance;
            $trades = [];
            $equityCurve = [];
            $currentPosition = null;
            $highestBalance = $initialBalance;
            $maxDrawdown = 0;

            // Process each candle
            foreach ($historicalData as $candle) {
                // Update equity curve
                $equityCurve[] = [
                    'timestamp' => $candle['timestamp'],
                    'equity' => $balance,
                    'drawdown' => $highestBalance - $balance,
                    'drawdown_percentage' => ($highestBalance - $balance) / $highestBalance * 100
                ];

                // Update highest balance and max drawdown
                if ($balance > $highestBalance) {
                    $highestBalance = $balance;
                }
                $currentDrawdown = ($highestBalance - $balance) / $highestBalance * 100;
                if ($currentDrawdown > $maxDrawdown) {
                    $maxDrawdown = $currentDrawdown;
                }

                // Check for entry signals
                if (!$currentPosition) {
                    $signal = $this->generateSignal($strategy, $candle, $parameters);
                    if ($signal) {
                        $currentPosition = $this->openPosition($backtest, $candle, $signal, $balance);
                        $trades[] = $currentPosition;
                    }
                }
                // Check for exit signals
                else {
                    $shouldExit = $this->checkExitSignal($strategy, $candle, $currentPosition, $parameters);
                    if ($shouldExit) {
                        $balance = $this->closePosition($currentPosition, $candle);
                        $currentPosition = null;
                    }
                }
            }

            // Close any open position at the end
            if ($currentPosition) {
                $balance = $this->closePosition($currentPosition, end($historicalData));
            }

            // Calculate final metrics
            $metrics = $this->calculateMetrics($trades, $initialBalance, $balance, $maxDrawdown);

            // Update backtest with results
            $backtest->update([
                'status' => 'completed',
                'final_balance' => $balance,
                'total_trades' => count($trades),
                'winning_trades' => $metrics['winning_trades'],
                'losing_trades' => $metrics['losing_trades'],
                'win_rate' => $metrics['win_rate'],
                'profit_factor' => $metrics['profit_factor'],
                'max_drawdown' => $maxDrawdown,
                'sharpe_ratio' => $metrics['sharpe_ratio'],
                'sortino_ratio' => $metrics['sortino_ratio']
            ]);

            // Save trades and equity curve
            $this->saveTrades($backtest, $trades);
            $this->saveEquityCurve($backtest, $equityCurve);

            $this->notificationService->notifyBacktestCompleted($backtest);

            return $backtest;
        } catch (\Exception $e) {
            Log::error('Backtest failed', [
                'backtest_id' => $backtest->id,
                'error' => $e->getMessage()
            ]);

            $backtest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            $this->notificationService->notifyBacktestFailed($backtest, $e->getMessage());

            throw $e;
        }
    }

    protected function getHistoricalData(Backtest $backtest): array
    {
        // TODO: Implement historical data fetching from exchange
        return [];
    }

    protected function openTrade(Backtest $backtest, array $candle, string $side, float $balance): array
    {
        $quantity = $this->calculatePositionSize($balance, $candle['close']);

        return [
            'backtest_id' => $backtest->id,
            'symbol' => $backtest->symbol,
            'side' => $side,
            'entry_price' => $candle['close'],
            'exit_price' => 0,
            'quantity' => $quantity,
            'entry_time' => $candle['timestamp'],
            'exit_time' => null,
            'profit_loss' => 0,
            'profit_loss_percentage' => 0,
            'stop_loss' => $this->calculateStopLoss($candle['close'], $side),
            'take_profit' => $this->calculateTakeProfit($candle['close'], $side),
            'trailing_stop' => null,
            'highest_price' => $candle['high'],
            'lowest_price' => $candle['low'],
            'exit_reason' => '',
        ];
    }

    protected function closeTrade(array $trade, array $candle, string $reason): array
    {
        $trade['exit_price'] = $candle['close'];
        $trade['exit_time'] = $candle['timestamp'];
        $trade['exit_reason'] = $reason;

        if ($trade['side'] === 'long') {
            $trade['profit_loss'] = ($trade['exit_price'] - $trade['entry_price']) * $trade['quantity'];
        } else {
            $trade['profit_loss'] = ($trade['entry_price'] - $trade['exit_price']) * $trade['quantity'];
        }

        $trade['profit_loss_percentage'] = ($trade['profit_loss'] / ($trade['entry_price'] * $trade['quantity'])) * 100;

        return $trade;
    }

    protected function updateTradePrices(array $trade, array $candle): array
    {
        $trade['highest_price'] = max($trade['highest_price'], $candle['high']);
        $trade['lowest_price'] = min($trade['lowest_price'], $candle['low']);

        // Update trailing stop if needed
        if ($trade['side'] === 'long' && $candle['high'] > $trade['highest_price']) {
            $newStop = $candle['high'] * 0.99; // 1% below highest price
            if ($newStop > $trade['trailing_stop']) {
                $trade['trailing_stop'] = $newStop;
            }
        } elseif ($trade['side'] === 'short' && $candle['low'] < $trade['lowest_price']) {
            $newStop = $candle['low'] * 1.01; // 1% above lowest price
            if ($trade['trailing_stop'] === null || $newStop < $trade['trailing_stop']) {
                $trade['trailing_stop'] = $newStop;
            }
        }

        return $trade;
    }

    protected function checkExitSignal(array $trade, array $candle, array $signals): ?string
    {
        // Check stop loss
        if ($trade['side'] === 'long' && $candle['low'] <= $trade['stop_loss']) {
            return 'stop_loss';
        } elseif ($trade['side'] === 'short' && $candle['high'] >= $trade['stop_loss']) {
            return 'stop_loss';
        }

        // Check take profit
        if ($trade['side'] === 'long' && $candle['high'] >= $trade['take_profit']) {
            return 'take_profit';
        } elseif ($trade['side'] === 'short' && $candle['low'] <= $trade['take_profit']) {
            return 'take_profit';
        }

        // Check trailing stop
        if ($trade['trailing_stop']) {
            if ($trade['side'] === 'long' && $candle['low'] <= $trade['trailing_stop']) {
                return 'trailing_stop';
            } elseif ($trade['side'] === 'short' && $candle['high'] >= $trade['trailing_stop']) {
                return 'trailing_stop';
            }
        }

        // Check exit signals
        if ($trade['side'] === 'long' && $signals['should_sell']) {
            return 'signal';
        } elseif ($trade['side'] === 'short' && $signals['should_buy']) {
            return 'signal';
        }

        return null;
    }

    protected function calculatePositionSize(float $balance, float $price): float
    {
        $riskPerTrade = 0.02; // 2% risk per trade
        return ($balance * $riskPerTrade) / $price;
    }

    protected function calculateStopLoss(float $price, string $side): float
    {
        $stopLossPercentage = 0.02; // 2% stop loss
        return $side === 'long'
            ? $price * (1 - $stopLossPercentage)
            : $price * (1 + $stopLossPercentage);
    }

    protected function calculateTakeProfit(float $price, string $side): float
    {
        $takeProfitPercentage = 0.04; // 4% take profit
        return $side === 'long'
            ? $price * (1 + $takeProfitPercentage)
            : $price * (1 - $takeProfitPercentage);
    }

    protected function calculateUnrealizedPnL(array $trade, array $candle): float
    {
        if ($trade['side'] === 'long') {
            return ($candle['close'] - $trade['entry_price']) * $trade['quantity'];
        } else {
            return ($trade['entry_price'] - $candle['close']) * $trade['quantity'];
        }
    }

    protected function updateBacktestResults(Backtest $backtest, array $trades, float $finalBalance): void
    {
        $winningTrades = array_filter($trades, fn($trade) => $trade['profit_loss'] > 0);
        $losingTrades = array_filter($trades, fn($trade) => $trade['profit_loss'] <= 0);

        $totalTrades = count($trades);
        $winningTradesCount = count($winningTrades);
        $losingTradesCount = count($losingTrades);

        $winRate = $totalTrades > 0 ? ($winningTradesCount / $totalTrades) * 100 : 0;

        $totalProfit = array_sum(array_map(fn($trade) => $trade['profit_loss'], $winningTrades));
        $totalLoss = abs(array_sum(array_map(fn($trade) => $trade['profit_loss'], $losingTrades)));
        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : 0;

        $backtest->update([
            'final_balance' => $finalBalance,
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTradesCount,
            'losing_trades' => $losingTradesCount,
            'win_rate' => $winRate,
            'profit_factor' => $profitFactor,
            'max_drawdown' => BacktestEquityCurve::where('backtest_id', $backtest->id)
                ->max('drawdown_percentage'),
            'sharpe_ratio' => $this->calculateSharpeRatio($backtest),
            'sortino_ratio' => $this->calculateSortinoRatio($backtest),
        ]);
    }

    protected function calculateSharpeRatio(Backtest $backtest): float
    {
        $equityCurve = BacktestEquityCurve::where('backtest_id', $backtest->id)
            ->orderBy('timestamp')
            ->get();

        if ($equityCurve->count() < 2) {
            return 0;
        }

        $returns = [];
        for ($i = 1; $i < $equityCurve->count(); $i++) {
            $returns[] = ($equityCurve[$i]->equity - $equityCurve[$i-1]->equity) / $equityCurve[$i-1]->equity;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStandardDeviation($returns);

        return $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0;
    }

    protected function calculateSortinoRatio(Backtest $backtest): float
    {
        $equityCurve = BacktestEquityCurve::where('backtest_id', $backtest->id)
            ->orderBy('timestamp')
            ->get();

        if ($equityCurve->count() < 2) {
            return 0;
        }

        $returns = [];
        for ($i = 1; $i < $equityCurve->count(); $i++) {
            $returns[] = ($equityCurve[$i]->equity - $equityCurve[$i-1]->equity) / $equityCurve[$i-1]->equity;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $downsideReturns = array_filter($returns, fn($r) => $r < 0);
        $downsideDeviation = $this->calculateStandardDeviation($downsideReturns);

        return $downsideDeviation > 0 ? ($avgReturn / $downsideDeviation) * sqrt(252) : 0;
    }

    protected function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $squaredDiffs = array_map(fn($value) => pow($value - $mean, 2), $values);
        return sqrt(array_sum($squaredDiffs) / ($count - 1));
    }

    // ... rest of the existing methods ...
}
