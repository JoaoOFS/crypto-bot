<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TechnicalAnalysisService;
use App\Services\ExchangeService;
use Illuminate\Support\Facades\Cache;
use Mockery;

class TechnicalAnalysisServiceTest extends TestCase
{
    protected $technicalAnalysisService;
    protected $exchangeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exchangeService = Mockery::mock(ExchangeService::class);
        $this->technicalAnalysisService = new TechnicalAnalysisService($this->exchangeService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculates_rsi_correctly()
    {
        $ohlcv = [
            ['timestamp' => 1, 'open' => 100, 'high' => 110, 'low' => 90, 'close' => 105, 'volume' => 1000],
            ['timestamp' => 2, 'open' => 105, 'high' => 115, 'low' => 95, 'close' => 110, 'volume' => 1200],
            ['timestamp' => 3, 'open' => 110, 'high' => 120, 'low' => 100, 'close' => 115, 'volume' => 1500],
            ['timestamp' => 4, 'open' => 115, 'high' => 125, 'low' => 105, 'close' => 120, 'volume' => 1800],
            ['timestamp' => 5, 'open' => 120, 'high' => 130, 'low' => 110, 'close' => 125, 'volume' => 2000],
        ];

        $rsi = $this->technicalAnalysisService->calculateRSI($ohlcv, 3);

        $this->assertIsFloat($rsi);
        $this->assertGreaterThanOrEqual(0, $rsi);
        $this->assertLessThanOrEqual(100, $rsi);
    }

    public function test_calculates_macd_correctly()
    {
        $ohlcv = [
            ['timestamp' => 1, 'open' => 100, 'high' => 110, 'low' => 90, 'close' => 105, 'volume' => 1000],
            ['timestamp' => 2, 'open' => 105, 'high' => 115, 'low' => 95, 'close' => 110, 'volume' => 1200],
            ['timestamp' => 3, 'open' => 110, 'high' => 120, 'low' => 100, 'close' => 115, 'volume' => 1500],
            ['timestamp' => 4, 'open' => 115, 'high' => 125, 'low' => 105, 'close' => 120, 'volume' => 1800],
            ['timestamp' => 5, 'open' => 120, 'high' => 130, 'low' => 110, 'close' => 125, 'volume' => 2000],
        ];

        $macd = $this->technicalAnalysisService->calculateMACD($ohlcv);

        $this->assertIsArray($macd);
        $this->assertArrayHasKey('macd', $macd);
        $this->assertArrayHasKey('signal', $macd);
        $this->assertArrayHasKey('histogram', $macd);
    }

    public function test_calculates_sma_correctly()
    {
        $ohlcv = [
            ['timestamp' => 1, 'open' => 100, 'high' => 110, 'low' => 90, 'close' => 105, 'volume' => 1000],
            ['timestamp' => 2, 'open' => 105, 'high' => 115, 'low' => 95, 'close' => 110, 'volume' => 1200],
            ['timestamp' => 3, 'open' => 110, 'high' => 120, 'low' => 100, 'close' => 115, 'volume' => 1500],
            ['timestamp' => 4, 'open' => 115, 'high' => 125, 'low' => 105, 'close' => 120, 'volume' => 1800],
            ['timestamp' => 5, 'open' => 120, 'high' => 130, 'low' => 110, 'close' => 125, 'volume' => 2000],
        ];

        $sma = $this->technicalAnalysisService->calculateSMA($ohlcv, 3);

        $this->assertIsArray($sma);
        $this->assertCount(3, $sma);
        $this->assertIsFloat($sma[0]);
    }

    public function test_calculates_ema_correctly()
    {
        $ohlcv = [
            ['timestamp' => 1, 'open' => 100, 'high' => 110, 'low' => 90, 'close' => 105, 'volume' => 1000],
            ['timestamp' => 2, 'open' => 105, 'high' => 115, 'low' => 95, 'close' => 110, 'volume' => 1200],
            ['timestamp' => 3, 'open' => 110, 'high' => 120, 'low' => 100, 'close' => 115, 'volume' => 1500],
            ['timestamp' => 4, 'open' => 115, 'high' => 125, 'low' => 105, 'close' => 120, 'volume' => 1800],
            ['timestamp' => 5, 'open' => 120, 'high' => 130, 'low' => 110, 'close' => 125, 'volume' => 2000],
        ];

        $ema = $this->technicalAnalysisService->calculateEMA($ohlcv, 3);

        $this->assertIsArray($ema);
        $this->assertCount(5, $ema);
        $this->assertIsFloat($ema[0]);
    }

    public function test_generates_signals_correctly()
    {
        $ohlcv = [
            ['timestamp' => 1, 'open' => 100, 'high' => 110, 'low' => 90, 'close' => 105, 'volume' => 1000],
            ['timestamp' => 2, 'open' => 105, 'high' => 115, 'low' => 95, 'close' => 110, 'volume' => 1200],
            ['timestamp' => 3, 'open' => 110, 'high' => 120, 'low' => 100, 'close' => 115, 'volume' => 1500],
            ['timestamp' => 4, 'open' => 115, 'high' => 125, 'low' => 105, 'close' => 120, 'volume' => 1800],
            ['timestamp' => 5, 'open' => 120, 'high' => 130, 'low' => 110, 'close' => 125, 'volume' => 2000],
        ];

        $signals = $this->technicalAnalysisService->generateSignals($ohlcv);

        $this->assertIsArray($signals);
        foreach ($signals as $signal) {
            $this->assertArrayHasKey('type', $signal);
            $this->assertArrayHasKey('signal', $signal);
        }
    }

    public function test_calculates_backtest_metrics_correctly()
    {
        $ohlcv = [
            ['timestamp' => 1, 'open' => 100, 'high' => 110, 'low' => 90, 'close' => 105, 'volume' => 1000],
            ['timestamp' => 2, 'open' => 105, 'high' => 115, 'low' => 95, 'close' => 110, 'volume' => 1200],
            ['timestamp' => 3, 'open' => 110, 'high' => 120, 'low' => 100, 'close' => 115, 'volume' => 1500],
            ['timestamp' => 4, 'open' => 115, 'high' => 125, 'low' => 105, 'close' => 120, 'volume' => 1800],
            ['timestamp' => 5, 'open' => 120, 'high' => 130, 'low' => 110, 'close' => 125, 'volume' => 2000],
        ];

        $metrics = $this->technicalAnalysisService->calculateBacktestMetrics(
            $ohlcv,
            'RSI',
            ['period' => 14]
        );

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_trades', $metrics);
        $this->assertArrayHasKey('win_rate', $metrics);
        $this->assertArrayHasKey('profit_factor', $metrics);
        $this->assertArrayHasKey('max_drawdown', $metrics);
    }

    public function test_caches_analysis_results()
    {
        $symbol = 'BTC/USDT';
        $interval = '1h';

        $this->exchangeService->shouldReceive('getOHLCV')
            ->once()
            ->with($symbol, $interval)
            ->andReturn([
                ['timestamp' => 1, 'open' => 100, 'high' => 110, 'low' => 90, 'close' => 105, 'volume' => 1000],
                ['timestamp' => 2, 'open' => 105, 'high' => 115, 'low' => 95, 'close' => 110, 'volume' => 1200],
            ]);

        // First call should hit the exchange
        $result1 = $this->technicalAnalysisService->analyze($symbol, $interval);

        // Second call should use cache
        $result2 = $this->technicalAnalysisService->analyze($symbol, $interval);

        $this->assertEquals($result1, $result2);
    }
}
