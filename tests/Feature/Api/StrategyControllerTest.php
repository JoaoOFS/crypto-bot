<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Portfolio;
use App\Models\Strategy;
use Laravel\Sanctum\Sanctum;

class StrategyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Portfolio $portfolio;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuário de teste
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Criar portfólio de teste
        $this->portfolio = Portfolio::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_list_strategies()
    {
        Strategy::factory()->count(3)->create(['portfolio_id' => $this->portfolio->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/strategies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'portfolio_id',
                    'name',
                    'type',
                    'parameters',
                    'description',
                    'is_active',
                    'last_executed',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonCount(3);
    }

    /** @test */
    public function it_can_filter_strategies_by_portfolio()
    {
        $otherPortfolio = Portfolio::factory()->create(['user_id' => $this->user->id]);

        Strategy::factory()->count(2)->create(['portfolio_id' => $this->portfolio->id]);
        Strategy::factory()->create(['portfolio_id' => $otherPortfolio->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/strategies?portfolio_id=' . $this->portfolio->id);

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_can_filter_strategies_by_type()
    {
        Strategy::factory()->count(2)->create([
            'portfolio_id' => $this->portfolio->id,
            'type' => 'technical'
        ]);
        Strategy::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'type' => 'fundamental'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/strategies?type=technical');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_can_create_strategy()
    {
        $strategyData = [
            'portfolio_id' => $this->portfolio->id,
            'name' => 'Moving Average Crossover',
            'type' => 'technical',
            'parameters' => [
                'fast_period' => 9,
                'slow_period' => 21,
                'timeframe' => '1h'
            ],
            'description' => 'Strategy based on moving average crossover',
            'is_active' => true,
            'schedule' => '0 * * * *',
            'risk_level' => 'medium',
            'max_positions' => 5,
            'stop_loss' => 2.5,
            'take_profit' => 5.0
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/strategies', $strategyData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'portfolio_id',
                'name',
                'type',
                'parameters',
                'description',
                'is_active',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'portfolio_id' => $this->portfolio->id,
                'name' => 'Moving Average Crossover',
                'type' => 'technical',
                'description' => 'Strategy based on moving average crossover',
                'is_active' => true
            ]);

        $this->assertDatabaseHas('strategies', [
            'portfolio_id' => $this->portfolio->id,
            'name' => 'Moving Average Crossover',
            'type' => 'technical',
            'description' => 'Strategy based on moving average crossover',
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_show_strategy()
    {
        $strategy = Strategy::factory()->create(['portfolio_id' => $this->portfolio->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/strategies/' . $strategy->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'portfolio_id',
                'name',
                'type',
                'parameters',
                'description',
                'is_active',
                'last_executed',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'id' => $strategy->id,
                'portfolio_id' => $this->portfolio->id
            ]);
    }

    /** @test */
    public function it_can_update_strategy()
    {
        $strategy = Strategy::factory()->create(['portfolio_id' => $this->portfolio->id]);

        $updateData = [
            'name' => 'Updated Strategy',
            'type' => 'hybrid',
            'parameters' => [
                'fast_period' => 12,
                'slow_period' => 26,
                'timeframe' => '4h'
            ],
            'description' => 'Updated strategy description',
            'is_active' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/v1/strategies/' . $strategy->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'portfolio_id',
                'name',
                'type',
                'parameters',
                'description',
                'is_active',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'id' => $strategy->id,
                'name' => 'Updated Strategy',
                'type' => 'hybrid',
                'description' => 'Updated strategy description',
                'is_active' => false
            ]);

        $this->assertDatabaseHas('strategies', [
            'id' => $strategy->id,
            'name' => 'Updated Strategy',
            'type' => 'hybrid',
            'description' => 'Updated strategy description',
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_delete_strategy()
    {
        $strategy = Strategy::factory()->create(['portfolio_id' => $this->portfolio->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/v1/strategies/' . $strategy->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Strategy deleted successfully'
            ]);

        $this->assertDatabaseMissing('strategies', [
            'id' => $strategy->id
        ]);
    }

    /** @test */
    public function it_cannot_access_other_users_strategies()
    {
        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);
        $strategy = Strategy::factory()->create(['portfolio_id' => $otherPortfolio->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/strategies/' . $strategy->id);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/strategies', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['portfolio_id', 'name', 'type', 'parameters']);
    }

    /** @test */
    public function it_validates_strategy_type()
    {
        $strategyData = [
            'portfolio_id' => $this->portfolio->id,
            'name' => 'Invalid Strategy',
            'type' => 'invalid_type',
            'parameters' => [
                'param1' => 'value1'
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/strategies', $strategyData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_validates_risk_level()
    {
        $strategyData = [
            'portfolio_id' => $this->portfolio->id,
            'name' => 'Test Strategy',
            'type' => 'technical',
            'parameters' => [
                'param1' => 'value1'
            ],
            'risk_level' => 'invalid_level'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/strategies', $strategyData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['risk_level']);
    }

    public function test_user_can_list_strategies()
    {
        Strategy::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/strategies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'parameters',
                        'description',
                        'is_active',
                        'schedule',
                        'risk_level',
                        'max_positions',
                        'stop_loss',
                        'take_profit',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_user_can_create_strategy()
    {
        $strategyData = [
            'name' => 'Test Strategy',
            'type' => 'trend_following',
            'parameters' => [
                'timeframe' => '1h',
                'indicators' => ['RSI', 'MACD']
            ],
            'description' => 'Test strategy description',
            'is_active' => true,
            'schedule' => '0 0 * * *',
            'risk_level' => 'medium',
            'max_positions' => 5,
            'stop_loss' => 2.5,
            'take_profit' => 5.0
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/strategies', $strategyData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'parameters',
                    'description',
                    'is_active',
                    'schedule',
                    'risk_level',
                    'max_positions',
                    'stop_loss',
                    'take_profit',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('strategies', [
            'name' => 'Test Strategy',
            'user_id' => $this->user->id
        ]);
    }

    public function test_user_cannot_create_strategy_with_invalid_data()
    {
        $strategyData = [
            'name' => '',
            'type' => 'invalid_type',
            'parameters' => 'invalid_parameters',
            'risk_level' => 'invalid_level'
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/strategies', $strategyData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'parameters', 'risk_level']);
    }

    public function test_user_can_show_strategy()
    {
        $strategy = Strategy::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/strategies/' . $strategy->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'parameters',
                    'description',
                    'is_active',
                    'schedule',
                    'risk_level',
                    'max_positions',
                    'stop_loss',
                    'take_profit',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    public function test_user_cannot_show_other_users_strategy()
    {
        $otherUser = User::factory()->create();
        $strategy = Strategy::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/strategies/' . $strategy->id);

        $response->assertStatus(404);
    }

    public function test_user_can_update_strategy()
    {
        $strategy = Strategy::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'name' => 'Updated Strategy',
            'description' => 'Updated description',
            'is_active' => false,
            'risk_level' => 'high'
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/v1/strategies/' . $strategy->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'parameters',
                    'description',
                    'is_active',
                    'schedule',
                    'risk_level',
                    'max_positions',
                    'stop_loss',
                    'take_profit',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('strategies', [
            'id' => $strategy->id,
            'name' => 'Updated Strategy',
            'description' => 'Updated description',
            'is_active' => false,
            'risk_level' => 'high'
        ]);
    }

    public function test_user_cannot_update_other_users_strategy()
    {
        $otherUser = User::factory()->create();
        $strategy = Strategy::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $updateData = [
            'name' => 'Updated Strategy'
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/v1/strategies/' . $strategy->id, $updateData);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_strategy()
    {
        $strategy = Strategy::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/v1/strategies/' . $strategy->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Strategy deleted successfully']);

        $this->assertDatabaseMissing('strategies', [
            'id' => $strategy->id
        ]);
    }

    public function test_user_cannot_delete_other_users_strategy()
    {
        $otherUser = User::factory()->create();
        $strategy = Strategy::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/v1/strategies/' . $strategy->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('strategies', [
            'id' => $strategy->id
        ]);
    }
}
