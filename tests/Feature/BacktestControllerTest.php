<?php

namespace Tests\Feature;

use App\Models\Backtest;
use App\Models\TradingStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacktestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->strategy = TradingStrategy::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Strategy',
            'type' => 'trend_following',
            'parameters' => [
                'rsi_period' => 14,
                'rsi_overbought' => 70,
                'rsi_oversold' => 30,
            ],
        ]);
    }

    public function test_user_can_create_backtest()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/backtests', [
            'trading_strategy_id' => $this->strategy->id,
            'exchange_id' => 1,
            'symbol' => 'BTC/USDT',
            'timeframe' => '1h',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
            'initial_balance' => 1000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'trading_strategy_id',
                'exchange_id',
                'symbol',
                'timeframe',
                'start_date',
                'end_date',
                'initial_balance',
                'status',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('backtests', [
            'user_id' => $this->user->id,
            'trading_strategy_id' => $this->strategy->id,
            'symbol' => 'BTC/USDT',
            'timeframe' => '1h',
        ]);
    }

    public function test_user_cannot_create_backtest_with_invalid_strategy()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/backtests', [
            'trading_strategy_id' => 999,
            'exchange_id' => 1,
            'symbol' => 'BTC/USDT',
            'timeframe' => '1h',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
            'initial_balance' => 1000,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_view_their_backtests()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'trading_strategy_id' => $this->strategy->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/backtests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'trading_strategy_id',
                        'symbol',
                        'timeframe',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_user_can_view_specific_backtest()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'trading_strategy_id' => $this->strategy->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/backtests/{$backtest->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'user_id',
                'trading_strategy_id',
                'symbol',
                'timeframe',
                'status',
                'trading_strategy',
                'exchange',
                'created_at',
                'updated_at',
            ]);
    }

    public function test_user_cannot_view_other_users_backtest()
    {
        $otherUser = User::factory()->create();
        $backtest = Backtest::factory()->create([
            'user_id' => $otherUser->id,
            'trading_strategy_id' => $this->strategy->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/backtests/{$backtest->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_backtest()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'trading_strategy_id' => $this->strategy->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/backtests/{$backtest->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('backtests', ['id' => $backtest->id]);
    }

    public function test_user_cannot_delete_other_users_backtest()
    {
        $otherUser = User::factory()->create();
        $backtest = Backtest::factory()->create([
            'user_id' => $otherUser->id,
            'trading_strategy_id' => $this->strategy->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/backtests/{$backtest->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('backtests', ['id' => $backtest->id]);
    }

    public function test_user_can_view_backtest_equity_curve()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'trading_strategy_id' => $this->strategy->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/backtests/{$backtest->id}/equity-curve");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'backtest_id',
                    'timestamp',
                    'equity',
                    'drawdown',
                    'drawdown_percentage',
                ],
            ]);
    }

    public function test_user_can_view_backtest_trades()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'trading_strategy_id' => $this->strategy->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/backtests/{$backtest->id}/trades");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'backtest_id',
                    'symbol',
                    'side',
                    'entry_price',
                    'exit_price',
                    'quantity',
                    'entry_time',
                    'exit_time',
                    'profit_loss',
                    'profit_loss_percentage',
                ],
            ]);
    }

    public function test_user_can_view_backtest_performance()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'trading_strategy_id' => $this->strategy->id,
            'total_trades' => 100,
            'winning_trades' => 60,
            'losing_trades' => 40,
            'win_rate' => 60,
            'profit_factor' => 1.5,
            'max_drawdown' => 15,
            'sharpe_ratio' => 1.2,
            'sortino_ratio' => 1.5,
            'initial_balance' => 1000,
            'final_balance' => 1500,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/backtests/{$backtest->id}/performance");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_trades',
                'winning_trades',
                'losing_trades',
                'win_rate',
                'profit_factor',
                'max_drawdown',
                'sharpe_ratio',
                'sortino_ratio',
                'initial_balance',
                'final_balance',
                'net_profit',
                'net_profit_percentage',
            ])
            ->assertJson([
                'total_trades' => 100,
                'winning_trades' => 60,
                'losing_trades' => 40,
                'win_rate' => 60,
                'profit_factor' => 1.5,
                'max_drawdown' => 15,
                'sharpe_ratio' => 1.2,
                'sortino_ratio' => 1.5,
                'initial_balance' => 1000,
                'final_balance' => 1500,
                'net_profit' => 500,
                'net_profit_percentage' => 50,
            ]);
    }
}
