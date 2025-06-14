<?php

namespace App\Services\Analysis;

use App\Services\Crypto\BinanceService;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MarketAnalysisService
{
    protected $binanceService;
    protected $technicalAnalysisService;
    protected $notificationService;

    public function __construct(
        BinanceService $binanceService,
        TechnicalAnalysisService $technicalAnalysisService,
        NotificationService $notificationService
    ) {
        $this->binanceService = $binanceService;
        $this->technicalAnalysisService = $technicalAnalysisService;
        $this->notificationService = $notificationService;
    }

    public function analyzeMarket($symbol, $interval = '1h', $limit = 100)
    {
        try {
            // Obtém dados do mercado
            $klines = $this->binanceService->getKlines($symbol, $interval, $limit);

            // Extrai preços de fechamento
            $prices = array_column($klines, 4);

            // Calcula indicadores técnicos
            $sma20 = $this->technicalAnalysisService->calculateSMA($prices, 20);
            $sma50 = $this->technicalAnalysisService->calculateSMA($prices, 50);
            $rsi = $this->technicalAnalysisService->calculateRSI($prices);
            $macd = $this->technicalAnalysisService->calculateMACD($prices);

            // Analisa tendências
            $trend = $this->analyzeTrend($prices, $sma20, $sma50);

            // Analisa volume
            $volumes = array_column($klines, 5);
            $volumeAnalysis = $this->analyzeVolume($volumes);

            // Gera sinais de trading
            $signals = $this->generateSignals([
                'trend' => $trend,
                'rsi' => $rsi,
                'macd' => $macd,
                'volume' => $volumeAnalysis
            ]);

            return [
                'symbol' => $symbol,
                'interval' => $interval,
                'current_price' => end($prices),
                'trend' => $trend,
                'indicators' => [
                    'sma20' => end($sma20),
                    'sma50' => end($sma50),
                    'rsi' => end($rsi),
                    'macd' => $macd
                ],
                'volume_analysis' => $volumeAnalysis,
                'signals' => $signals
            ];
        } catch (\Exception $e) {
            Log::error('Market Analysis Error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function analyzeTrend($prices, $sma20, $sma50)
    {
        $currentPrice = end($prices);
        $currentSMA20 = end($sma20);
        $currentSMA50 = end($sma50);

        // Análise de tendência
        if ($currentPrice > $currentSMA20 && $currentSMA20 > $currentSMA50) {
            return 'alta';
        } elseif ($currentPrice < $currentSMA20 && $currentSMA20 < $currentSMA50) {
            return 'baixa';
        } else {
            return 'lateral';
        }
    }

    protected function analyzeVolume($volumes)
    {
        $averageVolume = array_sum($volumes) / count($volumes);
        $currentVolume = end($volumes);

        return [
            'current' => $currentVolume,
            'average' => $averageVolume,
            'trend' => $currentVolume > $averageVolume ? 'aumentando' : 'diminuindo',
            'strength' => abs(($currentVolume - $averageVolume) / $averageVolume) * 100
        ];
    }

    protected function generateSignals($analysis)
    {
        $signals = [];

        // Análise de RSI
        if ($analysis['rsi'] < 30) {
            $signals[] = [
                'type' => 'compra',
                'strength' => 'forte',
                'indicator' => 'RSI',
                'message' => 'RSI indica sobrevenda'
            ];
        } elseif ($analysis['rsi'] > 70) {
            $signals[] = [
                'type' => 'venda',
                'strength' => 'forte',
                'indicator' => 'RSI',
                'message' => 'RSI indica sobrecompra'
            ];
        }

        // Análise de MACD
        if ($analysis['macd']['histogram'] > 0 && $analysis['macd']['signal'] > 0) {
            $signals[] = [
                'type' => 'compra',
                'strength' => 'média',
                'indicator' => 'MACD',
                'message' => 'MACD indica tendência de alta'
            ];
        } elseif ($analysis['macd']['histogram'] < 0 && $analysis['macd']['signal'] < 0) {
            $signals[] = [
                'type' => 'venda',
                'strength' => 'média',
                'indicator' => 'MACD',
                'message' => 'MACD indica tendência de baixa'
            ];
        }

        // Análise de Volume
        if ($analysis['volume']['trend'] === 'aumentando' && $analysis['volume']['strength'] > 50) {
            $signals[] = [
                'type' => 'compra',
                'strength' => 'fraca',
                'indicator' => 'Volume',
                'message' => 'Volume em alta significativa'
            ];
        }

        return $signals;
    }

    public function monitorMarket($symbols, $interval = '1h')
    {
        try {
            $alerts = [];

            foreach ($symbols as $symbol) {
                $analysis = $this->analyzeMarket($symbol, $interval);

                // Verifica condições de alerta
                if (!empty($analysis['signals'])) {
                    foreach ($analysis['signals'] as $signal) {
                        if ($signal['strength'] === 'forte') {
                            $alerts[] = [
                                'symbol' => $symbol,
                                'signal' => $signal,
                                'price' => $analysis['current_price'],
                                'timestamp' => now()
                            ];
                        }
                    }
                }
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('Market Monitoring Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getMarketOverview()
    {
        try {
            $overview = Cache::remember('market_overview', 300, function () {
                $symbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'DOGEUSDT'];
                $overview = [];

                foreach ($symbols as $symbol) {
                    $analysis = $this->analyzeMarket($symbol);
                    $overview[$symbol] = [
                        'price' => $analysis['current_price'],
                        'trend' => $analysis['trend'],
                        'signals' => $analysis['signals']
                    ];
                }

                return $overview;
            });

            return $overview;
        } catch (\Exception $e) {
            Log::error('Market Overview Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
