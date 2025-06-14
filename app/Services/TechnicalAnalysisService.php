<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Exchange;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TechnicalAnalysisService
{
    protected $exchangeService;
    protected $cacheTime = 300; // 5 minutos

    public function __construct(ExchangeService $exchangeService)
    {
        $this->exchangeService = $exchangeService;
    }

    public function analyze(string $symbol, string $interval)
    {
        $cacheKey = "analysis_{$symbol}_{$interval}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($symbol, $interval) {
            $ohlcv = $this->exchangeService->getOHLCV($symbol, $interval);

            return [
                'symbol' => $symbol,
                'interval' => $interval,
                'indicators' => [
                    'rsi' => $this->calculateRSI($ohlcv),
                    'macd' => $this->calculateMACD($ohlcv),
                    'sma' => $this->calculateSMA($ohlcv),
                    'ema' => $this->calculateEMA($ohlcv),
                ],
                'signals' => $this->generateSignals($ohlcv),
                'timestamp' => now(),
            ];
        });
    }

    public function getIndicators(string $symbol, array $indicators)
    {
        $ohlcv = $this->exchangeService->getOHLCV($symbol);
        $result = [];

        foreach ($indicators as $indicator) {
            $method = 'calculate' . strtoupper($indicator);
            if (method_exists($this, $method)) {
                $result[$indicator] = $this->$method($ohlcv);
            }
        }

        return $result;
    }

    public function backtest(string $symbol, string $strategy, array $parameters, string $startDate, string $endDate)
    {
        $ohlcv = $this->exchangeService->getHistoricalOHLCV($symbol, $startDate, $endDate);

        return [
            'strategy' => $strategy,
            'parameters' => $parameters,
            'results' => $this->runBacktest($ohlcv, $strategy, $parameters),
            'metrics' => $this->calculateBacktestMetrics($ohlcv, $strategy, $parameters),
        ];
    }

    protected function calculateRSI(array $ohlcv, int $period = 14)
    {
        // Implementação do RSI
        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($ohlcv); $i++) {
            $change = $ohlcv[$i]['close'] - $ohlcv[$i-1]['close'];
            $gains[] = max($change, 0);
            $losses[] = max(-$change, 0);
        }

        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        $rs = $avgGain / ($avgLoss ?: 0.0001);
        return 100 - (100 / (1 + $rs));
    }

    protected function calculateMACD(array $ohlcv)
    {
        // Implementação do MACD
        $ema12 = $this->calculateEMA($ohlcv, 12);
        $ema26 = $this->calculateEMA($ohlcv, 26);
        $macdLine = $ema12 - $ema26;
        $signalLine = $this->calculateEMA([$macdLine], 9);

        return [
            'macd' => $macdLine,
            'signal' => $signalLine,
            'histogram' => $macdLine - $signalLine,
        ];
    }

    protected function calculateSMA(array $ohlcv, int $period = 20)
    {
        $prices = array_column($ohlcv, 'close');
        $sma = [];

        for ($i = $period - 1; $i < count($prices); $i++) {
            $sma[] = array_sum(array_slice($prices, $i - $period + 1, $period)) / $period;
        }

        return $sma;
    }

    protected function calculateEMA(array $ohlcv, int $period = 20)
    {
        $prices = array_column($ohlcv, 'close');
        $multiplier = 2 / ($period + 1);
        $ema = [$prices[0]];

        for ($i = 1; $i < count($prices); $i++) {
            $ema[] = ($prices[$i] - $ema[$i-1]) * $multiplier + $ema[$i-1];
        }

        return $ema;
    }

    protected function generateSignals(array $ohlcv)
    {
        $signals = [];
        $rsi = $this->calculateRSI($ohlcv);
        $macd = $this->calculateMACD($ohlcv);

        // RSI Signals
        if ($rsi < 30) {
            $signals[] = ['type' => 'RSI', 'signal' => 'OVERSOLD'];
        } elseif ($rsi > 70) {
            $signals[] = ['type' => 'RSI', 'signal' => 'OVERBOUGHT'];
        }

        // MACD Signals
        if ($macd['macd'] > $macd['signal']) {
            $signals[] = ['type' => 'MACD', 'signal' => 'BULLISH'];
        } elseif ($macd['macd'] < $macd['signal']) {
            $signals[] = ['type' => 'MACD', 'signal' => 'BEARISH'];
        }

        return $signals;
    }

    protected function runBacktest(array $ohlcv, string $strategy, array $parameters)
    {
        // Implementação do backtest
        $trades = [];
        $position = null;

        for ($i = 1; $i < count($ohlcv); $i++) {
            $signal = $this->generateSignals(array_slice($ohlcv, 0, $i + 1));

            if ($signal && !$position) {
                $position = [
                    'entry_price' => $ohlcv[$i]['close'],
                    'entry_time' => $ohlcv[$i]['timestamp'],
                    'type' => $signal[0]['signal'],
                ];
            } elseif ($position && $this->shouldExit($signal, $position)) {
                $trades[] = [
                    'entry' => $position,
                    'exit' => [
                        'price' => $ohlcv[$i]['close'],
                        'time' => $ohlcv[$i]['timestamp'],
                    ],
                    'profit' => $this->calculateProfit($position, $ohlcv[$i]['close']),
                ];
                $position = null;
            }
        }

        return $trades;
    }

    protected function calculateBacktestMetrics(array $ohlcv, string $strategy, array $parameters)
    {
        $trades = $this->runBacktest($ohlcv, $strategy, $parameters);

        if (empty($trades)) {
            return [
                'total_trades' => 0,
                'win_rate' => 0,
                'profit_factor' => 0,
                'max_drawdown' => 0,
            ];
        }

        $profits = array_column($trades, 'profit');
        $winningTrades = array_filter($profits, fn($p) => $p > 0);

        return [
            'total_trades' => count($trades),
            'win_rate' => count($winningTrades) / count($trades),
            'profit_factor' => $this->calculateProfitFactor($profits),
            'max_drawdown' => $this->calculateMaxDrawdown($profits),
        ];
    }

    protected function shouldExit(array $signals, array $position)
    {
        // Lógica de saída baseada nos sinais
        foreach ($signals as $signal) {
            if ($position['type'] === 'BULLISH' && $signal['signal'] === 'BEARISH') {
                return true;
            }
            if ($position['type'] === 'BEARISH' && $signal['signal'] === 'BULLISH') {
                return true;
            }
        }
        return false;
    }

    protected function calculateProfit(array $position, float $exitPrice)
    {
        if ($position['type'] === 'BULLISH') {
            return ($exitPrice - $position['entry_price']) / $position['entry_price'];
        }
        return ($position['entry_price'] - $exitPrice) / $position['entry_price'];
    }

    protected function calculateProfitFactor(array $profits)
    {
        $grossProfit = array_sum(array_filter($profits, fn($p) => $p > 0));
        $grossLoss = abs(array_sum(array_filter($profits, fn($p) => $p < 0)));

        return $grossLoss ? $grossProfit / $grossLoss : 0;
    }

    protected function calculateMaxDrawdown(array $profits)
    {
        $peak = 0;
        $maxDrawdown = 0;
        $currentValue = 0;

        foreach ($profits as $profit) {
            $currentValue += $profit;
            if ($currentValue > $peak) {
                $peak = $currentValue;
            }
            $drawdown = ($peak - $currentValue) / $peak;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }

        return $maxDrawdown;
    }
}
