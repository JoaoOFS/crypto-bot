<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Log;

class TechnicalAnalysisService
{
    public function calculateSMA($prices, $period)
    {
        try {
            $sma = [];
            $count = count($prices);

            for ($i = 0; $i < $count; $i++) {
                if ($i < $period - 1) {
                    $sma[] = null;
                    continue;
                }

                $sum = 0;
                for ($j = 0; $j < $period; $j++) {
                    $sum += $prices[$i - $j];
                }
                $sma[] = $sum / $period;
            }

            return $sma;
        } catch (\Exception $e) {
            Log::error('SMA Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateEMA($prices, $period)
    {
        try {
            $ema = [];
            $count = count($prices);
            $multiplier = 2 / ($period + 1);

            // Primeiro valor EMA é igual ao SMA
            $sum = 0;
            for ($i = 0; $i < $period; $i++) {
                $sum += $prices[$i];
            }
            $ema[$period - 1] = $sum / $period;

            // Calcula EMA para os valores restantes
            for ($i = $period; $i < $count; $i++) {
                $ema[$i] = ($prices[$i] - $ema[$i - 1]) * $multiplier + $ema[$i - 1];
            }

            return $ema;
        } catch (\Exception $e) {
            Log::error('EMA Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateRSI($prices, $period = 14)
    {
        try {
            $rsi = [];
            $count = count($prices);
            $gains = [];
            $losses = [];

            // Calcula ganhos e perdas
            for ($i = 1; $i < $count; $i++) {
                $change = $prices[$i] - $prices[$i - 1];
                $gains[] = $change > 0 ? $change : 0;
                $losses[] = $change < 0 ? abs($change) : 0;
            }

            // Primeiro valor RSI
            $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
            $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;
            $rs = $avgGain / ($avgLoss ?: 1);
            $rsi[$period] = 100 - (100 / (1 + $rs));

            // Calcula RSI para os valores restantes
            for ($i = $period + 1; $i < $count; $i++) {
                $avgGain = (($avgGain * ($period - 1)) + $gains[$i - 1]) / $period;
                $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i - 1]) / $period;
                $rs = $avgGain / ($avgLoss ?: 1);
                $rsi[$i] = 100 - (100 / (1 + $rs));
            }

            return $rsi;
        } catch (\Exception $e) {
            Log::error('RSI Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateMACD($prices, $fastPeriod = 12, $slowPeriod = 26, $signalPeriod = 9)
    {
        try {
            $fastEMA = $this->calculateEMA($prices, $fastPeriod);
            $slowEMA = $this->calculateEMA($prices, $slowPeriod);
            $macd = [];
            $signal = [];
            $histogram = [];

            // Calcula MACD
            for ($i = 0; $i < count($prices); $i++) {
                if ($i < $slowPeriod - 1) {
                    $macd[$i] = null;
                    $signal[$i] = null;
                    $histogram[$i] = null;
                    continue;
                }

                $macd[$i] = $fastEMA[$i] - $slowEMA[$i];
            }

            // Calcula Signal Line (EMA do MACD)
            $signal = $this->calculateEMA(array_values(array_filter($macd)), $signalPeriod);

            // Calcula Histogram
            for ($i = 0; $i < count($macd); $i++) {
                if ($i < $slowPeriod + $signalPeriod - 2) {
                    $histogram[$i] = null;
                    continue;
                }

                $histogram[$i] = $macd[$i] - $signal[$i - ($slowPeriod + $signalPeriod - 2)];
            }

            return [
                'macd' => $macd,
                'signal' => $signal,
                'histogram' => $histogram,
            ];
        } catch (\Exception $e) {
            Log::error('MACD Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function detectCrossovers($fastMA, $slowMA)
    {
        try {
            $crossovers = [];
            $count = count($fastMA);

            for ($i = 1; $i < $count; $i++) {
                if ($fastMA[$i] === null || $slowMA[$i] === null ||
                    $fastMA[$i - 1] === null || $slowMA[$i - 1] === null) {
                    continue;
                }

                // Bullish crossover
                if ($fastMA[$i - 1] <= $slowMA[$i - 1] && $fastMA[$i] > $slowMA[$i]) {
                    $crossovers[] = [
                        'type' => 'bullish',
                        'index' => $i,
                        'price' => $fastMA[$i],
                    ];
                }
                // Bearish crossover
                elseif ($fastMA[$i - 1] >= $slowMA[$i - 1] && $fastMA[$i] < $slowMA[$i]) {
                    $crossovers[] = [
                        'type' => 'bearish',
                        'index' => $i,
                        'price' => $fastMA[$i],
                    ];
                }
            }

            return $crossovers;
        } catch (\Exception $e) {
            Log::error('Crossover Detection Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generateSignals($prices, $config)
    {
        try {
            $signals = [];
            $rsi = $this->calculateRSI($prices, $config['rsi_period']);
            $macd = $this->calculateMACD(
                $prices,
                $config['macd_fast_period'],
                $config['macd_slow_period'],
                $config['macd_signal_period']
            );

            $count = count($prices);
            for ($i = 0; $i < $count; $i++) {
                if ($i < $config['macd_slow_period'] + $config['macd_signal_period'] - 2) {
                    continue;
                }

                $signal = [
                    'index' => $i,
                    'price' => $prices[$i],
                    'rsi' => $rsi[$i] ?? null,
                    'macd' => $macd['macd'][$i] ?? null,
                    'signal' => $macd['signal'][$i - ($config['macd_slow_period'] + $config['macd_signal_period'] - 2)] ?? null,
                    'histogram' => $macd['histogram'][$i] ?? null,
                    'action' => null,
                ];

                // RSI Signals
                if ($signal['rsi'] <= $config['rsi_oversold']) {
                    $signal['action'] = 'buy';
                } elseif ($signal['rsi'] >= $config['rsi_overbought']) {
                    $signal['action'] = 'sell';
                }

                // MACD Signals
                if ($signal['histogram'] > 0 && $signal['histogram'] > $config['macd_threshold']) {
                    $signal['action'] = 'buy';
                } elseif ($signal['histogram'] < 0 && abs($signal['histogram']) > $config['macd_threshold']) {
                    $signal['action'] = 'sell';
                }

                $signals[] = $signal;
            }

            return $signals;
        } catch (\Exception $e) {
            Log::error('Signal Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
