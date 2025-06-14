<?php

namespace Tests\Unit;

use App\Models\Backtest;
use App\Models\BacktestTrade;
use App\Models\BacktestEquityCurve;
use App\Models\TradingStrategy;
use App\Services\BacktestService;
use App\Services\TechnicalAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class BacktestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $backtestService;
    protected $technicalAnalysisService;
    protected $backtest;
    protected $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->technicalAnalysisService = Mockery::mock(TechnicalAnalysisService::class);
        $this->backtestService = new BacktestService($this->technicalAnalysisService);

        $this->strategy = TradingStrategy::factory()->create([
            'name' => 'Test Strategy',
            'type' => 'trend_following',
            'parameters' => [
                'rsi_period' => 14,
                'rsi_overbought' => 70,
                'rsi_oversold' => 30,
            ],
        ]);

        $this->backtest = Backtest::factory()->create([
            'trading_strategy_id' => $this->strategy->id,
            'symbol' => 'BTC/USDT',
            'timeframe' => '1h',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
            'initial_balance' => 1000,
            'status' => 'pending',
        ]);
    }

    public function test_run_backtest_updates_status()
    {
        $this->technicalAnalysisService
            ->shouldReceive('analyze')
            ->andReturn([
                'should_buy' => false,
                'should_sell' => false,
            ]);

        $this->backtestService->runBacktest($this->backtest);

        $this->assertEquals('completed', $this->backtest->fresh()->status);
    }

    public function test_run_backtest_creates_trades()
    {
        $this->technicalAnalysisService
            ->shouldReceive('analyze')
            ->andReturn([
                'should_buy' => true,
                'should_sell' => false,
            ]);

        $this->backtestService->runBacktest($this->backtest);

        $this->assertGreaterThan(0, BacktestTrade::where('backtest_id', $this->backtest->id)->count());
    }

    public function test_run_backtest_creates_equity_curve()
    {
        $this->technicalAnalysisService
            ->shouldReceive('analyze')
            ->andReturn([
                'should_buy' => true,
                'should_sell' => false,
            ]);

        $this->backtestService->runBacktest($this->backtest);

        $this->assertGreaterThan(0, BacktestEquityCurve::where('backtest_id', $this->backtest->id)->count());
    }

    public function test_run_backtest_updates_performance_metrics()
    {
        $this->technicalAnalysisService
            ->shouldReceive('analyze')
            ->andReturn([
                'should_buy' => true,
                'should_sell' => false,
            ]);

        $this->backtestService->runBacktest($this->backtest);

        $backtest = $this->backtest->fresh();
        $this->assertNotNull($backtest->total_trades);
        $this->assertNotNull($backtest->winning_trades);
        $this->assertNotNull($backtest->losing_trades);
        $this->assertNotNull($backtest->win_rate);
        $this->assertNotNull($backtest->profit_factor);
        $this->assertNotNull($backtest->max_drawdown);
        $this->assertNotNull($backtest->sharpe_ratio);
        $this->assertNotNull($backtest->sortino_ratio);
    }

    public function test_run_backtest_handles_exception()
    {
        $this->technicalAnalysisService
            ->shouldReceive('analyze')
            ->andThrow(new \Exception('Test error'));

        $this->backtestService->runBacktest($this->backtest);

        $backtest = $this->backtest->fresh();
        $this->assertEquals('failed', $backtest->status);
        $this->assertEquals('Test error', $backtest->error_message);
    }

    public function test_calculate_position_size()
    {
        $balance = 1000;
        $price = 50000;
        $quantity = $this->backtestService->calculatePositionSize($balance, $price);

        $this->assertEquals(0.4, $quantity); // 2% risk = 20, 20/50000 = 0.4
    }

    public function test_calculate_stop_loss()
    {
        $price = 50000;
        $longStop = $this->backtestService->calculateStopLoss($price, 'long');
        $shortStop = $this->backtestService->calculateStopLoss($price, 'short');

        $this->assertEquals(49000, $longStop); // 2% below
        $this->assertEquals(51000, $shortStop); // 2% above
    }

    public function test_calculate_take_profit()
    {
        $price = 50000;
        $longTp = $this->backtestService->calculateTakeProfit($price, 'long');
        $shortTp = $this->backtestService->calculateTakeProfit($price, 'short');

        $this->assertEquals(52000, $longTp); // 4% above
        $this->assertEquals(48000, $shortTp); // 4% below
    }

    public function test_calculate_unrealized_pnl()
    {
        $trade = [
            'side' => 'long',
            'entry_price' => 50000,
            'quantity' => 1,
        ];
        $candle = ['close' => 51000];

        $pnl = $this->backtestService->calculateUnrealizedPnL($trade, $candle);
        $this->assertEquals(1000, $pnl);

        $trade['side'] = 'short';
        $pnl = $this->backtestService->calculateUnrealizedPnL($trade, $candle);
        $this->assertEquals(-1000, $pnl);
    }

    public function test_calculate_sharpe_ratio()
    {
        $equityCurve = [
            ['equity' => 1000],
            ['equity' => 1100],
            ['equity' => 1050],
            ['equity' => 1150],
        ];

        $ratio = $this->backtestService->calculateSharpeRatio($equityCurve);
        $this->assertIsFloat($ratio);
    }

    public function test_calculate_sortino_ratio()
    {
        $equityCurve = [
            ['equity' => 1000],
            ['equity' => 1100],
            ['equity' => 1050],
            ['equity' => 1150],
        ];

        $ratio = $this->backtestService->calculateSortinoRatio($equityCurve);
        $this->assertIsFloat($ratio);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
