<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Portfolio;
use App\Models\Exchange;
use Laravel\Sanctum\Sanctum;

class ExchangeControllerTest extends TestCase
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
    public function it_can_list_exchanges()
    {
        // Criar algumas exchanges de teste
        Exchange::factory()->count(3)->create([
            'portfolio_id' => $this->portfolio->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/exchanges');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'is_active',
                        'description',
                        'testnet',
                        'rate_limit',
                        'last_sync',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(3);
    }

    /** @test */
    public function it_can_filter_exchanges_by_portfolio()
    {
        $otherPortfolio = Portfolio::factory()->create(['user_id' => $this->user->id]);

        Exchange::factory()->count(2)->create(['portfolio_id' => $this->portfolio->id]);
        Exchange::factory()->create(['portfolio_id' => $otherPortfolio->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/exchanges?portfolio_id=' . $this->portfolio->id);

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_can_create_exchange()
    {
        $exchangeData = [
            'name' => 'Test Exchange',
            'type' => 'spot',
            'api_key' => 'test-api-key',
            'api_secret' => 'test-api-secret',
            'is_active' => true,
            'description' => 'Test exchange description',
            'testnet' => false,
            'rate_limit' => 100
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/exchanges', $exchangeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'is_active',
                    'description',
                    'testnet',
                    'rate_limit',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'name' => 'Test Exchange',
                'type' => 'spot',
                'is_active' => true,
                'testnet' => false,
                'rate_limit' => 100
            ]);

        $this->assertDatabaseHas('exchanges', [
            'name' => 'Test Exchange',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_show_exchange()
    {
        $exchange = Exchange::factory()->create([
            'portfolio_id' => $this->portfolio->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/exchanges/{$exchange->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'is_active',
                    'description',
                    'testnet',
                    'rate_limit',
                    'last_sync',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'id' => $exchange->id,
                'portfolio_id' => $this->portfolio->id
            ]);
    }

    /** @test */
    public function it_can_update_exchange()
    {
        $exchange = Exchange::factory()->create([
            'portfolio_id' => $this->portfolio->id
        ]);

        $updateData = [
            'name' => 'Updated Exchange',
            'description' => 'Updated description',
            'is_active' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/v1/exchanges/{$exchange->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'is_active',
                    'description',
                    'testnet',
                    'rate_limit',
                    'last_sync',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'id' => $exchange->id,
                'name' => 'Updated Exchange',
                'description' => 'Updated description',
                'is_active' => false
            ]);

        $this->assertDatabaseHas('exchanges', [
            'id' => $exchange->id,
            'name' => 'Updated Exchange',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_delete_exchange()
    {
        $exchange = Exchange::factory()->create([
            'portfolio_id' => $this->portfolio->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1/exchanges/{$exchange->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Exchange deleted successfully']);

        $this->assertDatabaseMissing('exchanges', [
            'id' => $exchange->id
        ]);
    }

    /** @test */
    public function it_cannot_access_other_users_exchanges()
    {
        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $exchange = Exchange::factory()->create([
            'portfolio_id' => $otherPortfolio->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/exchanges/{$exchange->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/exchanges', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['portfolio_id', 'name', 'api_key', 'api_secret', 'type']);
    }

    /** @test */
    public function it_validates_exchange_type()
    {
        $exchangeData = [
            'portfolio_id' => $this->portfolio->id,
            'name' => 'Invalid Exchange',
            'api_key' => 'test-api-key',
            'api_secret' => 'test-api-secret',
            'type' => 'invalid_type'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/exchanges', $exchangeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
