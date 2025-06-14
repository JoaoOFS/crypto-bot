<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exchange;
use App\Models\TradingStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TradingStrategyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $exchange;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->exchange = Exchange::factory()->create();
    }

    public function test_can_list_strategies()
    {
        TradingStrategy::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'exchange_id' => $this->exchange->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/trading-strategies');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_strategy()
    {
        $data = [
            'name' => 'Test Strategy',
            'description' => 'Test Description',
            'type' => 'trend_following',
            'parameters' => [
                'period' => 14,
                'fast_period' => 12,
                'slow_period' => 26
            ],
            'exchange_id' => $this->exchange->id,
            'symbol' => 'BTC/USDT',
            'timeframe' => '1h',
            'risk_percentage' => 2,
            'max_open_trades' => 3,
            'stop_loss_percentage' => 5,
            'take_profit_percentage' => 10,
            'trailing_stop' => true,
            'trailing_stop_activation' => 5,
            'trailing_stop_distance' => 2
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/trading-strategies', $data);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'symbol' => $data['symbol']
                ]
            ]);
    }

    public function test_can_view_strategy()
    {
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $this->user->id,
            'exchange_id' => $this->exchange->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/trading-strategies/{$strategy->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $strategy->id,
                    'name' => $strategy->name
                ]
            ]);
    }

    public function test_can_update_strategy()
    {
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $this->user->id,
            'exchange_id' => $this->exchange->id
        ]);

        $data = [
            'name' => 'Updated Strategy',
            'risk_percentage' => 3
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/trading-strategies/{$strategy->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => $data['name'],
                    'risk_percentage' => $data['risk_percentage']
                ]
            ]);
    }

    public function test_can_delete_strategy()
    {
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $this->user->id,
            'exchange_id' => $this->exchange->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/trading-strategies/{$strategy->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('trading_strategies', ['id' => $strategy->id]);
    }

    public function test_can_toggle_strategy()
    {
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $this->user->id,
            'exchange_id' => $this->exchange->id,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/trading-strategies/{$strategy->id}/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_active' => false
                ]
            ]);
    }

    public function test_can_view_strategy_performance()
    {
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $this->user->id,
            'exchange_id' => $this->exchange->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/trading-strategies/{$strategy->id}/performance");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_trades',
                'winning_trades',
                'losing_trades',
                'win_rate',
                'total_profit',
                'total_profit_percentage',
                'average_win',
                'average_loss',
                'profit_factor',
                'max_drawdown'
            ]);
    }

    public function test_validates_strategy_creation()
    {
        $data = [
            'name' => '',
            'type' => 'invalid_type',
            'risk_percentage' => 101,
            'max_open_trades' => 0
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/trading-strategies', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'type',
                'risk_percentage',
                'max_open_trades'
            ]);
    }

    public function test_cannot_access_other_user_strategy()
    {
        $otherUser = User::factory()->create();
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $otherUser->id,
            'exchange_id' => $this->exchange->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/trading-strategies/{$strategy->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_update_other_user_strategy()
    {
        $otherUser = User::factory()->create();
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $otherUser->id,
            'exchange_id' => $this->exchange->id
        ]);

        $data = ['name' => 'Updated Strategy'];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/trading-strategies/{$strategy->id}", $data);

        $response->assertStatus(403);
    }

    public function test_cannot_delete_other_user_strategy()
    {
        $otherUser = User::factory()->create();
        $strategy = TradingStrategy::factory()->create([
            'user_id' => $otherUser->id,
            'exchange_id' => $this->exchange->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/trading-strategies/{$strategy->id}");

        $response->assertStatus(403);
    }
}
