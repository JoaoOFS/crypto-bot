<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\TradingStrategy;
use App\Models\Trade;
use App\Services\TradingStrategyService;
use App\Services\ExchangeService;
use App\Services\TechnicalAnalysisService;
use Mockery;

class TradingStrategyServiceTest extends TestCase
{
    protected $tradingStrategyService;
    protected $exchangeService;
    protected $technicalAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exchangeService = Mockery::mock(ExchangeService::class);
        $this->technicalAnalysisService = Mockery::mock(TechnicalAnalysisService::class);
        $this->tradingStrategyService = new TradingStrategyService(
            $this->exchangeService,
            $this->technicalAnalysisService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_executes_strategy_when_active()
    {
        $strategy = TradingStrategy::factory()->create([
            'is_active' => true,
            'type' => 'trend_following'
        ]);

        $this->technicalAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'signals' => [
                    ['type' => 'RSI', 'signal' => 'BULLISH'],
                    ['type' => 'MACD', 'signal' => 'BULLISH']
                ]
            ]);

        $this->exchangeService
            ->shouldReceive('getCurrentPrice')
            ->once()
            ->andReturn(50000.0);

        $this->exchangeService
            ->shouldReceive('getAccountBalance')
            ->once()
            ->andReturn(10000.0);

        $this->exchangeService
            ->shouldReceive('placeOrder')
            ->once();

        $this->tradingStrategyService->executeStrategy($strategy);

        $this->assertDatabaseHas('trades', [
            'trading_strategy_id' => $strategy->id,
            'side' => 'long'
        ]);
    }

    public function test_does_not_execute_strategy_when_inactive()
    {
        $strategy = TradingStrategy::factory()->create([
            'is_active' => false
        ]);

        $this->technicalAnalysisService
            ->shouldNotReceive('analyze');

        $this->tradingStrategyService->executeStrategy($strategy);

        $this->assertDatabaseMissing('trades', [
            'trading_strategy_id' => $strategy->id
        ]);
    }

    public function test_does_not_execute_strategy_when_max_trades_reached()
    {
        $strategy = TradingStrategy::factory()->create([
            'is_active' => true,
            'max_open_trades' => 1
        ]);

        Trade::factory()->create([
            'trading_strategy_id' => $strategy->id,
            'status' => 'open'
        ]);

        $this->technicalAnalysisService
            ->shouldNotReceive('analyze');

        $this->tradingStrategyService->executeStrategy($strategy);

        $this->assertDatabaseCount('trades', 1);
    }

    public function test_manages_open_trades()
    {
        $strategy = TradingStrategy::factory()->create([
            'is_active' => true,
            'stop_loss_percentage' => 5,
            'take_profit_percentage' => 10
        ]);

        $trade = Trade::factory()->create([
            'trading_strategy_id' => $strategy->id,
            'status' => 'open',
            'entry_price' => 50000.0,
            'side' => 'long',
            'stop_loss' => 47500.0,
            'take_profit' => 55000.0
        ]);

        $this->exchangeService
            ->shouldReceive('getCurrentPrice')
            ->once()
            ->andReturn(45000.0);

        $this->exchangeService
            ->shouldReceive('placeOrder')
            ->once();

        $this->tradingStrategyService->executeStrategy($strategy);

        $this->assertDatabaseHas('trades', [
            'id' => $trade->id,
            'status' => 'closed',
            'exit_price' => 45000.0
        ]);
    }

    public function test_calculates_strategy_performance()
    {
        $strategy = TradingStrategy::factory()->create();

        Trade::factory()->count(3)->create([
            'trading_strategy_id' => $strategy->id,
            'status' => 'closed',
            'profit_loss' => 100.0,
            'profit_loss_percentage' => 2.0
        ]);

        Trade::factory()->count(2)->create([
            'trading_strategy_id' => $strategy->id,
            'status' => 'closed',
            'profit_loss' => -50.0,
            'profit_loss_percentage' => -1.0
        ]);

        $performance = $this->tradingStrategyService->getStrategyPerformance($strategy);

        $this->assertEquals(5, $performance['total_trades']);
        $this->assertEquals(3, $performance['winning_trades']);
        $this->assertEquals(2, $performance['losing_trades']);
        $this->assertEquals(60, $performance['win_rate']);
        $this->assertEquals(200, $performance['total_profit']);
        $this->assertEquals(4, $performance['total_profit_percentage']);
    }

    public function test_handles_exception_during_execution()
    {
        $strategy = TradingStrategy::factory()->create([
            'is_active' => true
        ]);

        $this->technicalAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $this->tradingStrategyService->executeStrategy($strategy);

        $this->assertDatabaseMissing('trades', [
            'trading_strategy_id' => $strategy->id
        ]);
    }

    public function test_updates_trade_prices()
    {
        $strategy = TradingStrategy::factory()->create();
        $trade = Trade::factory()->create([
            'trading_strategy_id' => $strategy->id,
            'status' => 'open',
            'side' => 'long',
            'highest_price' => 50000.0
        ]);

        $this->exchangeService
            ->shouldReceive('getCurrentPrice')
            ->once()
            ->andReturn(51000.0);

        $this->tradingStrategyService->executeStrategy($strategy);

        $this->assertDatabaseHas('trades', [
            'id' => $trade->id,
            'highest_price' => 51000.0
        ]);
    }

    public function test_activates_trailing_stop()
    {
        $strategy = TradingStrategy::factory()->create([
            'trailing_stop' => true,
            'trailing_stop_activation' => 5,
            'trailing_stop_distance' => 2
        ]);

        $trade = Trade::factory()->create([
            'trading_strategy_id' => $strategy->id,
            'status' => 'open',
            'side' => 'long',
            'entry_price' => 50000.0,
            'highest_price' => 52500.0
        ]);

        $this->exchangeService
            ->shouldReceive('getCurrentPrice')
            ->once()
            ->andReturn(51450.0);

        $this->exchangeService
            ->shouldReceive('placeOrder')
            ->once();

        $this->tradingStrategyService->executeStrategy($strategy);

        $this->assertDatabaseHas('trades', [
            'id' => $trade->id,
            'status' => 'closed'
        ]);
    }
}
