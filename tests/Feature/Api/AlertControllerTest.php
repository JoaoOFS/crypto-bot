<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Portfolio;
use App\Models\Asset;
use App\Models\Alert;
use Laravel\Sanctum\Sanctum;

class AlertControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Portfolio $portfolio;
    private Asset $asset;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuário de teste
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Criar portfólio e ativo de teste
        $this->portfolio = Portfolio::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->asset = Asset::factory()->create([
            'portfolio_id' => $this->portfolio->id
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
    public function it_can_list_alerts()
    {
        Alert::factory()->count(3)->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'portfolio_id',
                    'asset_id',
                    'type',
                    'condition',
                    'value',
                    'is_active',
                    'last_triggered',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonCount(3);
    }

    /** @test */
    public function it_can_filter_alerts_by_portfolio()
    {
        $otherPortfolio = Portfolio::factory()->create(['user_id' => $this->user->id]);
        $otherAsset = Asset::factory()->create(['portfolio_id' => $otherPortfolio->id]);

        Alert::factory()->count(2)->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id
        ]);
        Alert::factory()->create([
            'portfolio_id' => $otherPortfolio->id,
            'asset_id' => $otherAsset->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/alerts?portfolio_id=' . $this->portfolio->id);

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_can_filter_alerts_by_asset()
    {
        $otherAsset = Asset::factory()->create(['portfolio_id' => $this->portfolio->id]);

        Alert::factory()->count(2)->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id
        ]);
        Alert::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $otherAsset->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/alerts?asset_id=' . $this->asset->id);

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_can_filter_alerts_by_type()
    {
        Alert::factory()->count(2)->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id,
            'type' => 'price'
        ]);
        Alert::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id,
            'type' => 'volume'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/alerts?type=price');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_can_create_alert()
    {
        $alertData = [
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id,
            'type' => 'price',
            'condition' => 'above',
            'value' => 50000,
            'is_active' => true,
            'description' => 'BTC price alert',
            'notification_channels' => ['email', 'telegram'],
            'cooldown_minutes' => 60
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/alerts', $alertData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'portfolio_id',
                'asset_id',
                'type',
                'condition',
                'value',
                'is_active',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'portfolio_id' => $this->portfolio->id,
                'asset_id' => $this->asset->id,
                'type' => 'price',
                'condition' => 'above',
                'value' => 50000,
                'is_active' => true
            ]);

        $this->assertDatabaseHas('alerts', [
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id,
            'type' => 'price',
            'condition' => 'above',
            'value' => 50000,
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_show_alert()
    {
        $alert = Alert::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/alerts/' . $alert->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'portfolio_id',
                'asset_id',
                'type',
                'condition',
                'value',
                'is_active',
                'last_triggered',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'id' => $alert->id,
                'portfolio_id' => $this->portfolio->id,
                'asset_id' => $this->asset->id
            ]);
    }

    /** @test */
    public function it_can_update_alert()
    {
        $alert = Alert::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id
        ]);

        $updateData = [
            'type' => 'volume',
            'condition' => 'below',
            'value' => 1000000,
            'is_active' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/v1/alerts/' . $alert->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'portfolio_id',
                'asset_id',
                'type',
                'condition',
                'value',
                'is_active',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'id' => $alert->id,
                'type' => 'volume',
                'condition' => 'below',
                'value' => 1000000,
                'is_active' => false
            ]);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'type' => 'volume',
            'condition' => 'below',
            'value' => 1000000,
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_delete_alert()
    {
        $alert = Alert::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/v1/alerts/' . $alert->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Alert deleted successfully'
            ]);

        $this->assertDatabaseMissing('alerts', [
            'id' => $alert->id
        ]);
    }

    /** @test */
    public function it_cannot_access_other_users_alerts()
    {
        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);
        $otherAsset = Asset::factory()->create(['portfolio_id' => $otherPortfolio->id]);
        $alert = Alert::factory()->create([
            'portfolio_id' => $otherPortfolio->id,
            'asset_id' => $otherAsset->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/alerts/' . $alert->id);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/alerts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['portfolio_id', 'type', 'condition', 'value']);
    }

    /** @test */
    public function it_validates_alert_type()
    {
        $alertData = [
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id,
            'type' => 'invalid_type',
            'condition' => 'above',
            'value' => 50000
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/alerts', $alertData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_validates_alert_condition()
    {
        $alertData = [
            'portfolio_id' => $this->portfolio->id,
            'asset_id' => $this->asset->id,
            'type' => 'price',
            'condition' => 'invalid_condition',
            'value' => 50000
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/alerts', $alertData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['condition']);
    }
}
